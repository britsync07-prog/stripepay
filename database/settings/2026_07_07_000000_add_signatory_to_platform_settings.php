<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // The person who signs off quotations ("Prepared by").
        $this->migrator->add('platform.signatory_name', null);
        $this->migrator->add('platform.signatory_title', null);
    }
};
