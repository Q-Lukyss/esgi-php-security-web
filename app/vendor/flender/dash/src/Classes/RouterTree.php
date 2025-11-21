<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Flender\Dash\Enums\Method;

class RouterTree implements \JsonSerializable
{
    /**
     * Summary of data
     * @var array<string, array<Method, RouteScheme>>
     */
    private array $data;

    /**
     * Summary of __construct
     * @param array[RouteScheme] $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Summary of match
     * @param string $path
     * @param array $matched_parameters
     * @return array<Method, RouteScheme>|null
     */
    public function match(string $path, array &$matched_parameters = [])
    {
        foreach ($this->data as $regex => $group) {
            $pattern = "#^{$regex}$#";
            if (preg_match($pattern, $path, $match_params)) {
                return $group;
            }
        }
        return null;
    }

    public function jsonSerialize() {
        return RouteScheme::toArray($this->data);
    }

}