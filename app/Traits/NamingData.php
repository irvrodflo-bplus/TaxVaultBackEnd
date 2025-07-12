<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait NamingData {
    protected function flatName(array $relations, $model = null) {
        $model = $model ?? $this->model ?? null;
        
        if (!$model) {
            throw new \InvalidArgumentException("Invalid model");
        }

        foreach ($relations as $relation => $attribute) {
            if (!$model->relationLoaded($relation)) {
                $model->setAttribute($relation, null);
                continue;
            }
            
            $value = $model->{$relation} ? $model->{$relation}->{$attribute} : null;
            
            unset($model->{$relation});

            $model->setAttribute($relation, $value);
        }
        
        return $model;
    }
}