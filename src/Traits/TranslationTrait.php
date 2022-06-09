<?php

namespace RonasIT\Support\Traits;

trait TranslationTrait
{
    public function scopeWithTranslation($query)
    {
        if (class_exists($this->getTranslationClass())) {
            $query->with('translation');
        }
    }

    public function translation()
    {
        $lang = session('lang', 'en');
        $translationClass = $this->getTranslationClass();

        return $this->hasOne($translationClass)->where('locale', $lang);
    }

    public function allTranslations()
    {
        return $this->hasMany($this->getTranslationClass());
    }

    public function getTranslationClass(): string
    {
        return get_class($this) . 'Translation';
    }
}
