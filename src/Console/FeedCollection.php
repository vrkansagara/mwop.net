<?php

/**
 * @copyright Copyright (c) Matthew Weier O'Phinney
 * @license http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

declare(strict_types=1);

namespace Mwop\Console;

use Tightenco\Collect\Support\Collection;

class FeedCollection extends Collection
{
    /**
     * Filter items through a filter chain.
     *
     * Passes each item through a collection of filters, keeping only
     * those items that pass all filters.
     *
     * @return static
     */
    public function filterChain(array $filters): self
    {
        $filters = Collection::make($filters);
        return $this->filter(function (mixed $item) use ($filters): bool {
            return $filters
                ->reduce(function ($keep, callable $filter) use ($item) {
                    if (! $keep) {
                        return $keep;
                    }

                    return $filter($item);
                }, true);
        });
    }
}
