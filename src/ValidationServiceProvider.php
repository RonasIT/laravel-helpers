<?php

namespace RonasIT\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use RonasIT\Support\Rules\DBTypeRangeRule;
use RonasIT\Support\Rules\ListExistsRule;
use RonasIT\Support\Rules\UniqueExceptOfAuthorizedUserRule;

class ValidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->extendValidator();
    }

    protected function extendValidator(): void
    {
        Validator::extend('unique_except_of_authorized_user', UniqueExceptOfAuthorizedUserRule::extend(...));

        Validator::extend('list_exists', ListExistsRule::extend(...));

        Validator::extend('db_type_range', DBTypeRangeRule::extend(...));
    }
}
