<?php

use AttributeRouter\Attribute\Route;
use AttributeRouter\DTO\RouteDto;
use AttributeRouter\Exception\DirectoryEmptyException;
use AttributeRouter\Exception\NotConfiguredServiceException;
use AttributeRouter\Exception\RouteNotFoundException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

final class Dispatcher
{
    private static $routes;

    /**
     * @throws DirectoryEmptyException
     * @throws NotConfiguredServiceException
     */
    public function __construct(
        private string $controllersNamespaceRoot,
        private string $controllersPath,
        private string $cachePath = '',
    ) {
        $this->init();
    }

    public function getHandler(UriInterface $route, RequestInterface $request): RouteDto
    {
        $routes = $this->getRoutesCollection();
        $key = $this->getRoutesKey($request->getMethod(), $route->getPath());
        if (!key_exists($key, $routes)) {
            if (str_contains($key, ':')) {
                list($method, $route) = explode(':', $key);
                $key = sprintf('[%s] %s', $method, $route);
            }
            throw new RouteNotFoundException("Route {$key} Not Found");
        }
        list($class, $method) = explode('::', $routes[$key]);
        return new RouteDto(class: $class, method: $method, type: '');
    }


    /**
     * @throws DirectoryEmptyException
     * @throws NotConfiguredServiceException
     * @throws Exception
     */
    private function init()
    {
        if (!$this->isControllerExist()) {
            throw new NotConfiguredServiceException(
                'Controller path is not specified or default namespace is not specified. Abort dispatching'
            );
        }
        $this->collectRoutes();
        if (strlen($this->cachePath) === 0 && !is_dir($this->cachePath)) {
            $this->cacheRoutesCollection();
        }
    }

    private function isControllerExist(): bool
    {
        return is_dir($this->controllersPath) && strlen($this->controllersNamespaceRoot) > 0;
    }

    /**
     * build routes collection
     * @throws DirectoryEmptyException
     */
    private function collectRoutes(): void
    {
        if (isset(self::$routes) and count(self::$routes) > 0) {
            return;
        }
        $files = scandir($this->controllersPath);
        if (!is_array($files)) {
            throw new DirectoryEmptyException('Was not able to scan the directory');
        }
        $routes = [];
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            if (!str_ends_with($file, '.php')) {
                continue;
            }
            if (is_dir(sprintf('%s/%s', $this->controllersPath, $file))) {
                //@todo add directory recursive logic
                continue;
            }
            $className = substr($file, 0, -4);
            try {
                $reflection = new ReflectionClass($this->controllersNamespaceRoot . $className);
                foreach ($reflection->getMethods() as $method) {
                    foreach ($method->getAttributes() as $attribute) {
                        if (Route::class !== $attribute->getName()) {
                            continue;
                        }
                        $targetController = $reflection->getName();
                        $methodName = $method->getName();
                        $controllerPlace = sprintf('%s::%s', $targetController, $methodName);
                        $arguments = $attribute->getArguments();
                        $path = $this->getRoutesKey($arguments['method'], $arguments['path']);
                        $routes[$path] = $controllerPlace;
                    }
                }
            } catch (ReflectionException $exception) {
            }
        }
        self::$routes = $routes;
    }

    // save routes to the cache
    private function cacheRoutesCollection(): void
    {
        $cacheFileStream = fopen($this->cachePath, 'rb');
        if ($cacheFileStream === false) {
            throw new Exception('Not able to open stream');
        }
        $routes = self::$routes;
        $content = fread($cacheFileStream, filesize($this->cachePath));
        fclose($cacheFileStream);
        $data = json_decode($content, true);
        $diff = array_diff($routes, $data);
        if (count($diff) > 0) {
            $data = json_encode($routes);
            $cacheFileStream = fopen($this->cachePath, 'w+');
            fwrite($cacheFileStream, $data);
            fclose($cacheFileStream);
        }
    }

    private function getRoutesCollection(): array
    {
        $this->isCachedRoutes() ? $this->getCollectionRoutesFromCache() : $this->collectRoutes();
        return self::$routes;
    }

    private function isCachedRoutes()
    {
        return is_dir($this->cachePath);
    }

    private function getCollectionRoutesFromCache(): array
    {
        $cache = file_get_contents($this->cachePath);
        if ($cache === false) {
            return [];
        }
        $routes = json_decode($cache, true);
        return $routes;
    }

    private function getRoutesKey($method, $path): string
    {
        return sprintf('%s:%s', $method, str_contains($path, '/api') ? $path : '/api' . $path);
    }
}
