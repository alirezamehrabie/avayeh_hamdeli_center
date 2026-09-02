<?php

namespace App\Exports\Concerns;

use App\Models\GateEntryDeliveryRecipient;
use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * Resolves the Entry Gate's "delivery to non-customer" declaration for exported gate rows.
 *
 * The declaration lives one row per (service, subject) rather than per item, so it is loaded once
 * per service and looked up per row instead of joined into the report query.
 */
trait ResolvesGateDeliveryMethod
{
    protected ?Collection $proxyRecipientsByPerson = null;

    protected ?Collection $proxyRecipientsByGuardian = null;

    protected function deliveryMethodHeadings(): array
    {
        return ['نحوه تحویل', 'گیرنده'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function deliveryMethodColumns(?Service $service, $row): array
    {
        $label = $this->proxyRecipientLabel($service, $row);

        return $label === ''
            ? ['تحویل به مددجو', '-']
            : ['تحویل به غیر از مددجو', $label];
    }

    protected function proxyRecipientLabel(?Service $service, $row): string
    {
        if (! $service) {
            return '';
        }

        $this->loadProxyRecipients($service);

        if ($row->guardian_id) {
            return (string) ($this->proxyRecipientsByGuardian[(int) $row->guardian_id] ?? '');
        }

        if ($row->person_id) {
            return (string) ($this->proxyRecipientsByPerson[(int) $row->person_id] ?? '');
        }

        return '';
    }

    protected function loadProxyRecipients(Service $service): void
    {
        if ($this->proxyRecipientsByPerson !== null) {
            return;
        }

        $records = GateEntryDeliveryRecipient::query()
            ->where('service_id', $service->id)
            ->where('is_proxy_delivery', true)
            ->get();

        $this->proxyRecipientsByPerson = $records->whereNotNull('person_id')->mapWithKeys(
            fn (GateEntryDeliveryRecipient $record) => [(int) $record->person_id => $record->recipient_label]
        );

        $this->proxyRecipientsByGuardian = $records->whereNotNull('guardian_id')->mapWithKeys(
            fn (GateEntryDeliveryRecipient $record) => [(int) $record->guardian_id => $record->recipient_label]
        );
    }
}
