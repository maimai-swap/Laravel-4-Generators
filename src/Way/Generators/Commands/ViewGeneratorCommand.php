<?php namespace Way\Generators\Commands;

use Illuminate\Support\Facades\File;
use Way\Generators\Generator;
use Way\Generators\Parsers\MigrationFieldsParser;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ViewGeneratorCommand extends GeneratorCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'generate:view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a view';

    protected $migrationFieldsParser;

    public function __construct(
        Generator $generator,
        MigrationFieldsParser $migrationFieldsParser
    )
    {
        $this->migrationFieldsParser = $migrationFieldsParser;

        parent::__construct($generator);
    }


    /**
     * Create directory tree for views,
     * and fire generator
     */
    public function fire()
    {
        $directoryPath = dirname($this->getFileGenerationPath());

        if ( ! File::exists($directoryPath))
        {
            File::makeDirectory($directoryPath, 0777, true);
        }

        parent::fire();
    }

    /**
     * The path where the file will be created
     *
     * @return mixed
     */
    protected function getFileGenerationPath()
    {
        $path = $this->getPathByOptionOrConfig('path', 'view_target_path');
        $viewName = str_replace('.', '/', $this->argument('viewName'));

        return sprintf('%s/%s.blade.php', $path, $viewName);
    }

    /**
     * Fetch the template data
     *
     * @return array
     */
    protected function getTemplateData()
    {
        $_fields = $this->option("fields");
        $fields = $this->migrationFieldsParser->parse($_fields);
        $forms = $this->getFormElements($fields);

        $resource = $this->option("resource");

        return [
            'FIELD_VALUES' => $this->getValues($fields, $resource),
            'HEADINGS' => $this->getHeadings($fields),
            'FIELDS' => $this->getFormElements($fields),
            'COLLECTION' => $this->getTableName($resource) ,
            'MODELS' => $this->getTableName($resource),
            'MODEL' => $this->getModelName($resource) ,
            'RESOURCE' => $resource,
            'CONTROLLER' => $this->getControllerName($resource),
            'PATH' => $this->getFileGenerationPath()
        ];
    }

    /**
     * Get path to the template for the generator
     *
     * @return mixed
     */
    protected function getTemplatePath()
    {

        $viewName = str_replace('.', '/', $this->argument('viewName'));
        var_dump($viewName);
        $ret = explode("/",$viewName);
        if ( count($ret) == 2 ) {
            $view_type = $ret[1];
            switch($view_type) {
                case "index":
                case "show":
                case "create":
                case "edit":
                    return $this->getPathByOptionOrConfig('templatePath', 'view_'.$view_type.'_template_path');
                default:
                    return $this->getPathByOptionOrConfig('templatePath', 'view_template_path');
                    break;
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['viewName', InputArgument::REQUIRED, 'The name of the desired view']
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['fields', null, InputOption::VALUE_OPTIONAL, 'Fields for the migration'],
            ['resource', null, InputOption::VALUE_OPTIONAL, 'Fields for the resource'],
        ];
    }


    /**
     * Get the name for the model
     *
     * @param $resource
     * @return string
     */
    protected function getModelName($resource)
    {
        return ucwords(str_singular(camel_case($resource)));
    }

    /**
     * Get the name for the controller
     *
     * @param $resource
     * @return string
     */
    protected function getControllerName($resource)
    {
        return ucwords(str_plural(camel_case($resource))) . 'Controller';
    }

    /**
     * Get the DB table name
     *
     * @param $resource
     * @return string
     */
    protected function getTableName($resource)
    {
        return str_plural($resource);
    }

    protected function getHeadings($fields) {
        $_fields = "";
        foreach ($fields as $field) {
            $f = $field["field"];
            $u_f = ucwords($f);
            $_fields .= "<th>$u_f</th>\n";
        }
        return $_fields;
    }

    protected function getShows($fields, $resource) {
        $_fields = "";
        foreach ($fields as $field) {
            $f = $field["field"];
            $u_f = ucwords($f);
            $_fields .= "<dt>$u_f</dt><dd>{{ $" . $resource . "->$f }}</dd>\n";
        }
        return $_fields;
    }

    protected function getValues($fields,$resource)
    {

        $_fields = "";
        foreach ($fields as $field) {
            $f = $field["field"];
            $u_f = ucwords($f);
            $_fields .= "<td>{{ $".$resource."->$f }}</td>\n";
        }
        return $_fields;
    }

    protected function getFormElements($fields) {
        $_fields = "";
        foreach( $fields as $field ) {
            $f = $field["field"];
            $u_f = ucwords($f);

            switch ($field["type"] ) {
                case "string":
                    $_fields .= "    {{ Form::label('$f', '$u_f:') }}{{ Form::text('$f') }}{{ Form::error('$f',".'$errors'.") }}\n";
                    break;
                case "date":
                    $_fields .= "    {{ Form::label('$f', '$u_f:') }}{{ Form::date('$f') }}{{ Form::error('$f'," . '$errors' . ") }}\n";
            }
        }
        return $_fields;
    }
}
