<?php
namespace Nos;

class Config_Common
{

    public static function load($class, $filter_data_mapping)
    {
        list($application_name, $file) = \Config::configFile($class);
        $file = 'common'.substr($file, strrpos($file, '/'));

        $config = \Config::loadConfiguration($application_name, $file);

        if (!isset($config['actions'])) {
            $config['actions'] = array();
        }
        $config['actions'] = static::process_actions($application_name, $class, $config);

        if (!isset($config['data_mapping'])) {
            $config['data_mapping'] = array();
        }

        $config['data_mapping'] = static::filter_data_mapping($config['data_mapping'], $filter_data_mapping);
        $config['data_mapping'] = static::process_data_mapping($application_name, $class, $config);

        return $config;
    }

    public static function process_actions($application_name, $model, $config)
    {
        $urls = array(
            'add' => 'action.tab.url',
            'edit' => 'action.tab.url',
            'delete' => 'action.dialog.contentUrl',
        );

        $actions_template = array(
            'add' => array(
                'label' => __('Add :model_label'),
                'action' => array(
                    'action' => 'nosTabs',
                    'method' => 'add',
                    'tab' => array(
                        'url' => 'insert_update?context={{context}}',
                        'label' => __('Add a new monkey'),
                    ),
                ),
                'context' => array(
                    'appdeskToolbar' => true
                ),
            ),
            'edit' => array(
                'action' => array(
                    'action' => 'nosTabs',
                    'tab' => array(
                        'url' => "insert_update/{{id}}",
                        'label' => __('Edit'),
                    ),
                ),
                'label' => __('Edit'),
                'primary' => true,
                'icon' => 'pencil',
                'context' => array(
                    'list' => true
                ),
            ),
            'delete' => array(
                'action' => array(
                    'action' => 'confirmationDialog',
                    'dialog' => array(
                        'contentUrl' => 'delete/{{id}}',
                        'title' => __('Delete'),
                    ),
                ),
                'label' => __('Delete'),
                'primary' => true,
                'icon' => 'trash',
                'red' => true,
                'context' => array(
                    'item' => true,
                    'list' => true
                ),
                'enabled' =>
                function($item) {
                    return !$item->is_new();
                },
            ),
            'visualise' => array(
                'label' => 'Visualise',
                'primary' => true,
                'iconClasses' => 'nos-icon16 nos-icon16-eye',
                'action' => array(
                    'action' => 'window.open',
                    'url' => '{{preview_url}}?_preview=1'
                ),
                'context' => array(
                    'item' => true,
                    'list' => true
                ),
                'enabled' =>
                function($item) {
                    if ($item::behaviours('Nos\Orm_Behaviour_Urlenhancer', false)) {
                        $url = $item->url_canonical(array('preview' => true));

                        return !$item->is_new() && !empty($url);
                    }
                    return false;
                },
            ),
            'share' => array(
                'label' => __('Share'),
                'iconClasses' => 'nos-icon16 nos-icon16-share',
                'action' => array(
                    'action' => 'share',
                    'data' => array(
                        'model_id' => '{{id}}',
                        'model_name' => '',
                    ),
                ),
                'context' => array(
                    'item' => true
                ),
                'enabled' =>
                function($item) {
                    return !$item->is_new();
                },
            )
        );


        $model_label = explode('/', $model);
        $model_label = $model_label[count($model_label) - 1];
        $model_label = explode('_', $model_label);
        $model_label = $model_label[count($model_label) - 1];

        if (!isset($config['controller'])) {
            $config['controller'] = strtolower($model_label);
        }

        if (!isset($config['labels'])) {
            $config['labels'] = array();
        }

        if ($model::behaviours('Nos\Orm_Behaviour_Urlenhancer', false) === false) {
            unset($actions_template['visualise']);
        }

        $generated_actions = array();
        foreach ($actions_template as $name => $template) {
            $generated_actions[$model.'.'.$name] = $template;

            if (isset($urls[$name])) {
                \Arr::set(
                    $generated_actions[$model.'.'.$name],
                    $urls[$name], 'admin/'.$application_name.'/'.$config['controller'].'/'.
                    \Arr::get($generated_actions[$model.'.'.$name], $urls[$name])
                );
            }

            if (isset($config['labels'][$name])) {
                $generated_actions[$model.'.'.$name]['label'] = $config['labels'][$name];
            }
            $generated_actions[$model.'.'.$name]['label'] = \Str::tr(
                $generated_actions[$model.'.'.$name]['label'],
                array('model_label' => $model_label)
            );

            if ($name == 'share') {
                $generated_actions[$model.'.'.$name]['action']['data']['model_name'] = $model;
            }
        }

        $actions = \Arr::merge($generated_actions, $config['actions']);

        foreach ($actions as $key => $action) {
            if ($action === false) {
                unset($actions[$key]);
            }
        }

        return $actions;
    }

    protected static function process_data_mapping($application_name, $class, $config)
    {
        if (!isset($config['data_mapping'])) {
            return array();
        }
        foreach ($config['data_mapping'] as $key => &$item) {
            if (is_array($item)) {
                // @todo two keys to process : appdesk and fieldset
                if (!isset($item['headerText']) && isset($item['title'])) {
                    $item['headerText'] = $item['title'];
                }
                if (!isset($item['column']) && !isset($item['value'])) {
                    $item['column'] = str_replace('->', '.', $key);
                }
                $relations = explode('->', $key);
                if (!isset($item['search_relation']) && count($relations) > 1) {
                    // @todo: support multilevel relations ?
                    $item['search_relation'] = $relations[0];
                }
                if (!isset($item['headerText'])) {
                    $item['visible'] = false;
                }
            } else if (is_string($item)) {
                $config['data_mapping'][$item] = array();
            }
        }

        return $config['data_mapping'];
    }

    protected static function filter_data_mapping($initial_data_mapping, $filter)
    {
        if ($filter != null) {
            $data_mapping = array();
            foreach ($filter as $key => $value) {
                $data_mapping_key = null;
                $data_mapping_extend = null;
                if (is_array($value)) {
                    $data_mapping_key = $key;
                    $data_mapping_extend = $value;
                } else {
                    $data_mapping_key = $value;
                }

                if (!is_array($initial_data_mapping[$data_mapping_key])) {
                    $data_mapping[$data_mapping_key] = $data_mapping_extend === null ?
                        $initial_data_mapping[$data_mapping_key] : $data_mapping_extend;
                } else {
                    if ($data_mapping_extend === null) {
                        $data_mapping_extend = array();
                    }
                    $data_mapping[$data_mapping_key] = \Arr::merge(
                        $initial_data_mapping[$data_mapping_key],
                        $data_mapping_extend
                    );
                }
            }
            return $data_mapping;
        } else {
            return $initial_data_mapping;
        }
    }
}