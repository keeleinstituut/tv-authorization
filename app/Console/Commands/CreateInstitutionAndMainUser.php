<?php

namespace App\Console\Commands;

use App\Rules\PersonalIdCodeRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateInstitutionAndMainUser extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:create-institution-and-user
                            {fname? : The forename of the user}
                            {sname? : The surname of the user}
                            {pin? : The personal identification code of the user}
                            {iname? : The name of the institution}';

    /**
     * @var string
     */
    protected $description = 'Command creates institution and main user for it with corresponding privileges';

    public function handle(): void
    {
        $argumentsDefinition = [
            'iname' => fn() => $this->argument('iname') ?: $this->ask('What is the name of the institution?'),
            'fname' => fn() => $this->argument('fname') ?: $this->ask('What is the forename of the main user?'),
            'sname' => fn() => $this->argument('sname') ?: $this->ask('What is the surname of the main user?'),
            'pin' => fn() => $this->argument('pin') ?: $this->ask('What is the personal identification code of the main user?')
        ];

        $arguments = $argumentsDefinition;
        $values = [];
        do {
            foreach ($arguments as $argument => $getter) {
                $values[$argument] = $getter();
            }

            $validator = Validator::make($values, [
                'iname' => ['required', 'min:2'],
                'fname' => ['required', 'min:2'],
                'sname' => ['required', 'min:2'],
                'pin' => ['required', new PersonalIdCodeRule],
            ]);

            $arguments = [];
            foreach ($validator->errors()->messages() as $attribute => $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
                $arguments[$attribute] = $argumentsDefinition[$attribute];
            }
        } while ($validator->fails());


        $this->info('The institution and main user were created successful!');
    }
}
