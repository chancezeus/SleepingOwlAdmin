<?php

namespace SleepingOwl\Admin\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SleepingOwl\Admin\Contracts\ModelConfigurationInterface;
use SleepingOwl\Admin\Contracts\Repositories\RepositoryInterface;
use SleepingOwl\Admin\Form\Element\DependentSelect;
use SleepingOwl\Admin\Form\Element\MultiSelectAjax;
use SleepingOwl\Admin\Form\Element\SelectAjax;

class FormElementController extends Controller
{
    /**
     * @param Request $request
     * @param ModelConfigurationInterface $model
     * @param string $field
     * @param int|null $id
     *
     * @return JsonResponse
     */
    public function dependentSelect(Request $request, ModelConfigurationInterface $model, $field, $id = null)
    {
        if (!is_null($id)) {
            $item = $model->getRepository()->find($id);
            if (is_null($item) || !$model->isEditable($item)) {
                return new JsonResponse([
                    'message' => trans('lang.message.access_denied'),
                ], 403);
            }

            $form = $model->fireEdit($id);
        } else {
            if (!$model->isCreatable()) {
                return new JsonResponse([
                    'message' => trans('lang.message.access_denied'),
                ], 403);
            }

            $form = $model->fireCreate();
        }

        /** @var DependentSelect $element */
        if (is_null($element = $form->getElement($field))) {
            return new JsonResponse([
                'message' => 'Element not found',
            ], 404);
        }

        $element->setAjaxParameters(
            $request->input('depdrop_all_params', [])
        );

        $options = $element->getOptions();

        if ($element->isNullable()) {
            $options = [null => trans('sleeping_owl::lang.select.nothing')] + $options;
        }

        return new JsonResponse([
            'output' => collect($options)->map(function ($value, $key) {
                return ['id' => $key, 'name' => $value];
            }),
            'selected' => $element->getValueFromModel(),
        ]);
    }

    /**
     * @param Request $request
     * @param ModelConfigurationInterface $model
     * @param string $field
     * @param int|null $id
     *
     * @return JsonResponse
     */
    public function multiselectSearch(Request $request, ModelConfigurationInterface $model, $field, $id = null)
    {
        if (!is_null($id)) {
            $item = $model->getRepository()->find($id);
            if (is_null($item) || !$model->isEditable($item)) {
                return new JsonResponse([
                    'message' => trans('lang.message.access_denied'),
                ], 403);
            }

            $form = $model->fireEdit($id);
        } else {
            if (!$model->isCreatable()) {
                return new JsonResponse([
                    'message' => trans('lang.message.access_denied'),
                ], 403);
            }

            $form = $model->fireCreate();
        }

        /** @var MultiSelectAjax $element */
        if (is_null($element = $form->getElement($field))) {
            return new JsonResponse([
                'message' => 'Element not found',
            ], 404);
        }

        $repository = app(RepositoryInterface::class);
        $repository->setModel($element->getModelForOptions());

        $query = $repository->getQuery();

        if (is_callable($filter = $element->getQueryFilter())) {
            $filter($element, $query);
        }

        $field = $request->field;

        if ($request->q) {
            $query->where($field, 'like', "%{$request->q}%");
        }

        return new JsonResponse(
            $query->get()
                ->map(function ($item) use ($field) {
                    return [
                        'tag_name' => $item->{$field},
                        'id' => $item->id,
                        'custom_name' => $item->custom_name,
                    ];
                })
        );
    }

    /**
     * @param Request $request
     * @param ModelConfigurationInterface $model
     * @param string $field
     * @param int|null $id
     *
     * @return JsonResponse
     */
    public function selectSearch(Request $request, ModelConfigurationInterface $model, $field, $id = null)
    {
        if (!is_null($id)) {
            $item = $model->getRepository()->find($id);
            if (is_null($item) || !$model->isEditable($item)) {
                return new JsonResponse([
                    'message' => trans('lang.message.access_denied'),
                ], 403);
            }

            $form = $model->fireEdit($id);
        } else {
            if (!$model->isCreatable()) {
                return new JsonResponse([
                    'message' => trans('lang.message.access_denied'),
                ], 403);
            }

            $form = $model->fireCreate();
        }

        /** @var SelectAjax $element */
        if (is_null($element = $form->getElement($field))) {
            return new JsonResponse([
                'message' => 'Element not found',
            ], 404);
        }

        $repository = app(RepositoryInterface::class);
        $repository->setModel($element->getModelForOptions());

        /** @var Builder $query */
        $query = $repository->getQuery();

        if (is_callable($filter = $element->getQueryFilter())) {
            $filter($element, $query);
        }

        $field = $request->field;

        if ($request->q) {
            $query->where($field, 'like', "%{$request->q}%");
        }

        return new JsonResponse(
            $query->get()
                ->map(function ($item) use ($field) {
                    return [
                        'tag_name' => $item->{$field},
                        'id' => $item->id,
                        'custom_name' => $item->custom_name,
                    ];
                })
        );
    }
}
