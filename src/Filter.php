<?php

declare(strict_types=1);

/**
 * Implementace q={} filtrovani nad PHP poli (simulace DB filtru z php-core).
 *
 * Podporovane operatory:
 *   eq       – rovnost (vychozi kdyz neni operator uveden)
 *   neq      – nerovnost
 *   start    – zacina na
 *   end      – konci na
 *   contains – obsahuje (case-insensitive)
 *   gte      – vetsi nebo rovno
 *   lte      – mensi nebo rovno
 *   gt       – vetsi
 *   lt       – mensi
 *   in       – hodnota je v poli
 *   isnull   – hodnota je null (value se ignoruje)
 *   notnull  – hodnota neni null (value se ignoruje)
 *
 * Priklad q parametru:
 *   {"name": "test"}
 *   {"name": {"value": "test", "operator": "contains"}}
 *   {"price": {"value": 10, "operator": "gte"}}
 *   {"category": {"value": ["electronics","gadgets"], "operator": "in"}}
 */
class Filter
{
    /**
     * Aplikuje q={} filter na pole polozek.
     *
     * @param  array  $data   Vstupni pole asociativnich poli
     * @param  string $rawQ   JSON retezec z parametru q
     * @return array          Filtrovane pole (re-indexovano)
     */
    public static function apply(array $data, string $rawQ): array
    {
        if ($rawQ === '') {
            return $data;
        }

        $q = json_decode($rawQ, true);
        if (!is_array($q) || empty($q)) {
            return $data;
        }

        return array_values(array_filter($data, function (array $item) use ($q): bool {
            foreach ($q as $field => $spec) {
                if (is_array($spec)) {
                    $value    = $spec['value'] ?? null;
                    $operator = (string) ($spec['operator'] ?? 'eq');
                } else {
                    $value    = $spec;
                    $operator = 'eq';
                }

                $itemVal = $item[$field] ?? null;

                if (!self::_match($itemVal, $value, $operator)) {
                    return false;
                }
            }
            return true;
        }));
    }

    /**
     * Stránkování pole.
     *
     * @return array{items: array, total: int}
     */
    public static function paginate(array $data, int $page, int $limit): array
    {
        $total  = count($data);
        $offset = ($page - 1) * $limit;
        return [
            'items' => array_slice($data, $offset, $limit),
            'total' => $total,
        ];
    }

    private static function _match(mixed $itemVal, mixed $filterVal, string $operator): bool
    {
        return match ($operator) {
            'eq'      => $itemVal == $filterVal,
            'neq'     => $itemVal != $filterVal,
            'start'   => str_starts_with((string) $itemVal, (string) $filterVal),
            'end'     => str_ends_with((string) $itemVal, (string) $filterVal),
            'contains'=> (bool) preg_match('/' . preg_quote((string) $filterVal, '/') . '/ui', (string) $itemVal),
            'gte'     => is_numeric($itemVal) && is_numeric($filterVal) ? (float)$itemVal >= (float)$filterVal : $itemVal >= $filterVal,
            'lte'     => is_numeric($itemVal) && is_numeric($filterVal) ? (float)$itemVal <= (float)$filterVal : $itemVal <= $filterVal,
            'gt'      => is_numeric($itemVal) && is_numeric($filterVal) ? (float)$itemVal > (float)$filterVal  : $itemVal > $filterVal,
            'lt'      => is_numeric($itemVal) && is_numeric($filterVal) ? (float)$itemVal < (float)$filterVal  : $itemVal < $filterVal,
            'in'      => is_array($filterVal) && in_array($itemVal, $filterVal),
            'isnull'  => $itemVal === null,
            'notnull' => $itemVal !== null,
            default   => $itemVal == $filterVal,
        };
    }
}
