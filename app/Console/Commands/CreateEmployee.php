<?php

namespace App\Console\Commands;

use App\Actions\Employees\CreateEmployee as CreateEmployeeAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('employee:create {--name= : Full name} {--email= : Login email} {--color= : Hex color, e.g. #4fc3f7} {--password= : Initial password (asked interactively when omitted)}')]
#[Description('Create an employee (there is no public registration)')]
class CreateEmployee extends Command
{
    public function handle(CreateEmployeeAction $createEmployee): int
    {
        $palette = config('employee_colors');

        $name = $this->option('name') ?? text(__('employees.cli.name'), required: true);
        $email = $this->option('email') ?? text(__('employees.cli.email'), required: true);
        $color = $this->option('color') ?? select(__('employees.cli.color'), $palette);
        $password = $this->option('password') ?? password(__('employees.cli.password'), required: true);

        try {
            $employee = $createEmployee([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'color' => $color,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->components->error($message);
                }
            }

            return self::FAILURE;
        }

        $this->components->info(__('employees.cli.created', ['name' => $employee->name, 'email' => $employee->email]));

        return self::SUCCESS;
    }
}
