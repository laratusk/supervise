<?php

declare(strict_types=1);

namespace Laratusk\Supervise\Services;

use Illuminate\Support\Facades\Validator;
use Laratusk\Supervise\Exceptions\ValidationException;

class ConfigValidator
{
    /**
     * @param  array<string, mixed>  $config
     *
     * @throws ValidationException
     */
    public function validate(array $config): void
    {
        /** @var array<string, mixed> $workers */
        $workers = $config['workers'] ?? [];

        /** @var array<string, list<string>> $groups */
        $groups = $config['groups'] ?? [];

        $data = ['workers' => $workers, 'groups' => $groups];

        /** @var array<string, list<string>> $rules */
        $rules = [
            'workers' => ['required', 'array', 'min:1'],
        ];

        /** @var array<string, mixed> $worker */
        foreach ($workers as $name => $worker) {
            $rules["workers.{$name}.type"] = ['required', 'string', 'in:horizon,queue,reverb'];

            if (($worker['type'] ?? '') === 'queue') {
                $rules["workers.{$name}.queue"] = ['required', 'array', 'min:1'];
                $rules["workers.{$name}.queue.*"] = ['string'];
            }

            if (isset($worker['numprocs'])) {
                $rules["workers.{$name}.numprocs"] = ['integer', 'min:1'];
            }

            if (isset($worker['connection'])) {
                $rules["workers.{$name}.connection"] = ['string'];
            }

            if (isset($worker['tries'])) {
                $rules["workers.{$name}.tries"] = ['integer', 'min:1'];
            }

            if (isset($worker['max_time'])) {
                $rules["workers.{$name}.max_time"] = ['integer', 'min:1'];
            }

            if (isset($worker['sleep'])) {
                $rules["workers.{$name}.sleep"] = ['integer', 'min:0'];
            }

            if (isset($worker['stopwaitsecs'])) {
                $rules["workers.{$name}.stopwaitsecs"] = ['integer', 'min:0'];
            }

            if (isset($worker['startretries'])) {
                $rules["workers.{$name}.startretries"] = ['integer', 'min:0'];
            }

            if (isset($worker['priority'])) {
                $rules["workers.{$name}.priority"] = ['integer'];
            }
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            /** @var array<string, array<int, string>> $errors */
            $errors = $validator->errors()->toArray();
            throw new ValidationException($errors);
        }

        /** @var list<string> $workerNames */
        $workerNames = array_keys($workers);

        /** @var array<string, array<int, string>> $groupErrors */
        $groupErrors = [];

        /** @var list<string> $workerList */
        foreach ($groups as $groupName => $workerList) {
            foreach ($workerList as $workerName) {
                if (! in_array($workerName, $workerNames, true)) {
                    $groupErrors["groups.{$groupName}"][] = "Worker '{$workerName}' referenced in group '{$groupName}' does not exist.";
                }
            }
        }

        if (! empty($groupErrors)) {
            throw new ValidationException($groupErrors);
        }
    }
}
