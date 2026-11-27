<?php

namespace App\Contracts;

/**
 * A model whose files are delivered by link and that has something to say about them.
 */
interface Deliverable
{
    /**
     * Rows shown to recipients and in alerts, keyed by label.
     *
     * @return array<string, string>
     */
    public function deliveryDetails(): array;

    public function deliveryAdminUrl(): string;
}
