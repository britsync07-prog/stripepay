<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('platform.currency_code', 'USD');
        $this->migrator->add('platform.currency_symbol', '$');
        $this->migrator->add('platform.tax_rate', 0.0);
        $this->migrator->add('platform.company_name', config('app.name'));
        $this->migrator->add('platform.company_email', null);
        $this->migrator->add('platform.company_address', null);
        $this->migrator->add('platform.vat_number', null);
        $this->migrator->add('platform.invoice_footer', null);
    }
};
