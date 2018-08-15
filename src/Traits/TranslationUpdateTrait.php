<?php

namespace RonasIT\Support\Traits;

trait TranslationUpdateTrait
{
    public function updateWithTranslations($id, $data)
    {
        $translations = array_pull($data, 'translations');

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

        return $this->firstWithRelations(['id' => $id], ['allTranslations']);
    }
}