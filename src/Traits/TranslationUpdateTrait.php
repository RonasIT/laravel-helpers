<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Arr; 

trait TranslationUpdateTrait
{
    public function updateWithTranslations($id, $data)
    {
        $translations = Arr::pull($data, 'translations');

        if (empty($translations)) {
            return $this->update(['id' => $id], $data);
        }

        $modelInstance = new $this->model;

        $foreignKey = $modelInstance->allTranslations()->getForeignKeyName();

        $translationModel = $modelInstance->getTranslationClass();

        foreach ($translations as $translation) {
            $translationModel::where([
                $foreignKey => $id,
                'locale' => $translation['locale']
            ])->update($translation);
        }

        return $this->withRelations(['allTranslations'])->find($id);
    }
}
