<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Seeds the quotation letterhead ("Prepared by") from the BlackDesck Agency
 * details. Only fills a value that is still empty (or the framework default),
 * so it never overwrites something an admin has already set at /admin/settings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->fill('platform.signatory_name', 'Fadoua Fakir');
        $this->fill('platform.signatory_title', 'Managing Director');
        $this->fill('platform.company_name', 'BlackDesck Agency LLC');
        $this->fill('platform.company_address', 'Wyoming, United States');
    }

    private function fill(string $property, string $value): void
    {
        $this->migrator->update(
            $property,
            fn ($current) => (blank($current) || $current === config('app.name')) ? $value : $current,
        );
    }
};
