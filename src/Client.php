<?php

declare(strict_types=1);
/**
 * This file is part of jet.
 *
 * @link     https://github.com/friendsofhyperf/jet
 * @document https://github.com/friendsofhyperf/jet/blob/main/README.md
 * @contact  huangdijia@gmail.com
 * @license  https://github.com/friendsofhyperf/jet/blob/main/LICENSE
 */
namespace FriendsOfHyperf\Jet;

use FriendsOfHyperf\Jet\Exception\RecvFailedException;
use FriendsOfHyperf\Jet\Exception\ServerException;
use FriendsOfHyperf\Jet\Transporter\GrpcTransporter;
use Throwable;

class Client
{
    /**
     * The Jet major version.
     */
    public const MAJOR_VERSION = 2;

    /**
     * @var Metadata
     */
    protected $metadata;

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @throws Throwable
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $tries = $this->metadata->getTries();
        $path = $this->metadata->getPathGenerator()->generate($this->metadata->getName(), $name);
        $transporter = $this->metadata->getTransporter();
        $dataFormatter = $this->metadata->getDataFormatter();
        $packer = $this->metadata->getPacker();

        if ($transporter->getLoadBalancer()) {
            $nodeCount = count($transporter->getLoadBalancer()->getNodes());
            if ($nodeCount > $tries) {
                $tries = $nodeCount;
            }
        }

        $callback = function () use ($transporter, $dataFormatter, $packer, $path, $arguments) {
            $data = $dataFormatter->formatRequest([$path, $arguments, uniqid()]);

            $transporter->send($packer->pack($data));

            $ret = $transporter->recv();

            if ($transporter instanceof GrpcTransporter) {
                if (! isset($ret[0])) {
                    throw new ServerException(
                        [
                            'metadata' => $ret[1]->metadata ?? '',
                            'code' => $ret[1]->code ?? '',
                            'message' => $ret[1]->details ?? '',
                        ]
                    );
                }

                return $ret[0];
            }

            if (! is_string($ret)) {
                throw new RecvFailedException('Recv failed');
            }

            return with($packer->unpack($ret), function ($data) {
                if (array_key_exists('result', $data)) {
                    return $data['result'];
                }

                throw new ServerException($data['error'] ?? []);
            });
        };

        return retry($tries, $callback);
    }
}
