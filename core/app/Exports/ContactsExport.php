<?php

namespace App\Exports;

use App\Models\Contact;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ContactsExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize
{
    protected ?int $createdBy;
    protected string $countryTitleColumn;
    protected string $fallbackCountryTitleColumn;

    public function __construct(?int $createdBy = null, ?string $languageCode = null)
    {
        $this->createdBy = $createdBy;

        $allowedLanguages = ['ar', 'en', 'ch', 'hi', 'es', 'ru', 'pt', 'fr', 'de', 'th', 'br'];
        $defaultLanguage = config('smartend.default_language', 'en');

        if (!in_array($languageCode, $allowedLanguages, true)) {
            $languageCode = in_array($defaultLanguage, $allowedLanguages, true) ? $defaultLanguage : 'en';
        }

        $this->countryTitleColumn = 'title_'.$languageCode;
        $this->fallbackCountryTitleColumn = 'title_'.(in_array($defaultLanguage, $allowedLanguages, true) ? $defaultLanguage : 'en');
    }

    public function query(): Builder
    {
        return Contact::query()
            ->leftJoin('contacts_groups', 'contacts.group_id', '=', 'contacts_groups.id')
            ->leftJoin('countries', 'contacts.country_id', '=', 'countries.id')
            ->when($this->createdBy, function (Builder $query) {
                $query->where('contacts.created_by', $this->createdBy);
            })
            ->select([
                'contacts.id',
                'contacts.first_name',
                'contacts.last_name',
                'contacts.company',
                'contacts.email',
                'contacts.phone',
                'contacts.city',
                'contacts.address',
                'contacts.notes',
                'contacts.status',
                'contacts_groups.name as group_name',
                'countries.'.$this->countryTitleColumn.' as country_title',
                'countries.'.$this->fallbackCountryTitleColumn.' as fallback_country_title',
            ])
            ->orderBy('contacts.id', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            __('backend.firstName'),
            __('backend.lastName'),
            __('backend.fullName'),
            __('backend.group'),
            __('backend.companyName'),
            __('backend.contactEmail'),
            __('backend.contactPhone'),
            __('backend.country'),
            __('backend.city'),
            __('backend.contactAddress'),
            __('backend.notes'),
            __('backend.status'),
        ];
    }

    public function map($contact): array
    {
        return [
            $contact->id,
            $contact->first_name,
            $contact->last_name,
            trim($contact->first_name.' '.$contact->last_name),
            $contact->group_name,
            $contact->company,
            $contact->email,
            $contact->phone,
            $contact->country_title ?: $contact->fallback_country_title,
            $contact->city,
            $contact->address,
            $contact->notes,
            $this->statusLabel((int) $contact->status),
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    protected function statusLabel(int $status): string
    {
        return match ($status) {
            1 => __('backend.active'),
            2 => __('backend.notActive'),
            default => __('backend.waitActivation'),
        };
    }

    protected function dateTimeValue($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
