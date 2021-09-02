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

use Exception;
use FriendsOfHyperf\Jet\Contract\DataFormatterInterface;
use FriendsOfHyperf\Jet\Contract\PackerInterface;
use FriendsOfHyperf\Jet\Contract\PathGeneratorInterface;
use FriendsOfHyperf\Jet\Contract\TransporterInterface;
use InvalidArgumentException;

class ClientFactory
{
    /**
     * User agent.
     * @var string
     */
    protected static $userAgent;

    /**
     * Set user agent.
     */
    public static function setUserAgent(string $userAgent): void
    {
        self::$userAgent = $userAgent;
    }

    /**
     * Get user agent.
     */
    public static function getUserAgent(): string
    {
        return self::$userAgent ?: sprintf('jet/2.0 php/%s curl/%s', PHP_VERSION, curl_version()['version']);
    }

    /**
     * Create a client.
     * @param null|int|string|TransporterInterface $transporter transporter, protocol, timeout or null
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function create(string $service, $transporter = null, ?PackerInterface $packer = null, ?DataFormatterInterface $dataFormatter = null, ?PathGeneratorInterface $pathGenerator = null, ?int $tries = null): Client
    {
        if (! $metadata = ServiceManager::get($service)) {
            $metadata = new Metadata($service);

            if (RegistryManager::isRegistered(RegistryManager::DEFAULT)) {
                $metadata->setRegistry(RegistryManager::get(RegistryManager::DEFAULT));
            }

            if ($transporter instanceof TransporterInterface) {
                $metadata->setTransporter($transporter);
            } elseif (is_numeric($transporter)) {
                $metadata->setTimeout($transporter);
            } elseif (is_string($transporter)) {
                $metadata->setProtocol($transporter);
            }

            if ($packer) {
                $metadata->setPacker($packer);
            }

            if ($dataFormatter) {
                $metadata->setDataFormatter($dataFormatter);
            }

            if ($pathGenerator) {
                $metadata->setPathGenerator($pathGenerator);
            }

            if ($tries) {
                $metadata->setTries($tries);
            }
        }

        return new Client($metadata);
    }
}
