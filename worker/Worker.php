<?php

use App\Kernel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Debug\Debug;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

class Worker
{
    /** @var Kernel */
    private $kernel;

    /** @var Container */
    private $container;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $delay;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        // The check is to ensure we don't use .env in production
        if (!isset($_SERVER['APP_ENV'])) {
            if (!class_exists(Dotenv::class)) {
                throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
            }
            (new Dotenv())->load(__DIR__ . '/../.env');
        }

        $env   = $_SERVER['APP_ENV']   ?? 'dev';
        $debug = $_SERVER['APP_DEBUG'] ?? ('prod' !== $env);

        if ($debug) {
            umask(0000);

            Debug::enable();
        }
        set_time_limit(0);
        $this->kernel = new Kernel($env, $debug);
        $this->kernel->boot();
        $this->container = $this->kernel->getContainer();
        $this->logger    = $this->container->get('public.logger');
        $this->delay     = 60;
    }

    public function getService(string $service): object
    {
        try {
            return $this->container->get($service);
        } catch (Exception $e) {
            throw new \RuntimeException('Error while getting service ' . $service . ' : ' . $e->getMessage());
        }
    }

    public function getKernel(): Kernel
    {
        return $this->kernel;
    }

    public function setKernel(Kernel $kernel): self
    {
        $this->kernel = $kernel;

        return $this;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function setContainer(Container $container): self
    {
        $this->container = $container;

        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function setDelay(int $delay): self
    {
        $this->delay = $delay;

        return $this;
    }
}
