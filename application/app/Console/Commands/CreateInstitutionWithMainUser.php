<?php

namespace App\Console\Commands;

use App\Actions\CreateInstitutionWithMainUserAction;
use App\DataTransferObjects\InstitutionData;
use App\DataTransferObjects\UserData;
use App\Rules\PersonalIdCodeRule;
use App\Rules\PhoneNumberRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CreateInstitutionWithMainUser extends Command
{
    /**
     * @var string
     */
    protected $signature = 'institution:create-with-main-user
                            {fname? : The forename of the user}
                            {sname? : The surname of the user}
                            {pic? : The personal identification code of the user}
                            {email? : The email of the user}
                            {phone? : The phone number of the user}
                            {iname? : The name of the institution}
                            {isname? : The short name of the institution}
                            {logo? : The logo of the institution}';

    /**
     * @var string
     */
    protected $description = 'Command creates institution and main user for it with corresponding privileges';

    /**
     * @throws Throwable
     */
    public function handle(CreateInstitutionWithMainUserAction $createInstitutionWithMainUserAction): int
    {
        $argumentsDefinition = [
            'fname' => fn () => $this->argument('fname') ?: $this->ask('What is the forename of the main user?'),
            'sname' => fn () => $this->argument('sname') ?: $this->ask('What is the surname of the main user?'),
            'pic' => fn () => $this->argument('pic') ?: $this->ask('What is the personal identification code of the main user?'),
            'email' => fn () => $this->argument('email') ?: $this->ask('What is the email of the main user?'),
            'phone' => fn () => $this->argument('phone') ?: $this->ask('What is the phone number of the main user?'),
            'iname' => fn () => $this->argument('iname') ?: $this->ask('What is the name of the institution?'),
            'isname' => fn () => $this->argument('isname') ?: $this->ask('What is the short name of the institution?'),
            'logo' => fn () => $this->argument('logo') ?: $this->ask('What is the logo of the institution?'),
        ];

        $arguments = $argumentsDefinition;
        $values = [];
        do {
            foreach ($arguments as $argument => $getter) {
                $values[$argument] = $getter();
            }

            $validator = Validator::make($values, [
                'iname' => ['required', 'min:2'],
                'isname' => ['required', 'min:2', 'max:3'],
                'fname' => ['required', 'min:2'],
                'sname' => ['required', 'min:2'],
                'email' => ['required', 'email'],
                'phone' => ['required', new PhoneNumberRule],
                'logo' => ['nullable', 'url'],
                'pic' => ['required', new PersonalIdCodeRule],
            ]);

            $arguments = [];
            foreach ($validator->errors()->messages() as $attribute => $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
                $arguments[$attribute] = $argumentsDefinition[$attribute];
            }
        } while ($validator->fails());

        try {
            $createInstitutionWithMainUserAction->execute(
                new InstitutionData(
                    $values['iname'],
                    $values['isname'],
                    $values['logo']
                ),
                new UserData(
                    $values['pic'],
                    $values['email'],
                    $values['sname'],
                    $values['fname'],
                    $values['phone'],
                )
            );
        } catch (Throwable $e) {
            $this->error('The institution and main user creation failed! Reason: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('The institution and main user were created successful!');

        return self::SUCCESS;
    }
}
