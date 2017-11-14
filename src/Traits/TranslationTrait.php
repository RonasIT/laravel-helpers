<?php
namespace RonasIT\Support\Traits;

trait TranslationTrait {
    public function scopeWithTranslation($query) {
        if (class_exists($this->getTranslationClass())) {
            $query->with('translation');
        }
    }

    public function translation()
    {
        $lang = session('lang', 'en');

        return $this->hasOne($this->getTranslationClass())
            ->where('locale', $lang);
    }

    protected function getTranslationClass() {
        return get_class($this) . 'Translation';
    }
}