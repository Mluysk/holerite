<?php

declare(strict_types=1);

namespace Holerite\Repositories;

use DateTimeImmutable;
use Holerite\Database\Connection;
use Holerite\Models\Employee;
use PDO;

final class EmployeeRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    /**
     * @return Employee[]
     */
    public function all(): array
    {
        $statement = $this->pdo->query('SELECT * FROM employees ORDER BY name');
        $rows = $statement->fetchAll();

        return array_map(fn (array $row): Employee => $this->hydrate($row), $rows);
    }

    public function find(int $id): ?Employee
    {
        $statement = $this->pdo->prepare('SELECT * FROM employees WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function create(Employee $employee): Employee
    {
        $statement = $this->pdo->prepare('INSERT INTO employees (name, cpf, base_salary, department, position, hire_date, termination_date) VALUES (:name, :cpf, :base_salary, :department, :position, :hire_date, :termination_date)');
        $statement->execute([
            'name' => $employee->getName(),
            'cpf' => $employee->getCpf(),
            'base_salary' => $employee->getBaseSalary(),
            'department' => $employee->getDepartment(),
            'position' => $employee->getPosition(),
            'hire_date' => $employee->getHireDate()->format('Y-m-d'),
            'termination_date' => $employee->getTerminationDate()?->format('Y-m-d'),
        ]);

        $employee->setId((int) $this->pdo->lastInsertId());

        return $employee;
    }

    public function update(Employee $employee): void
    {
        $statement = $this->pdo->prepare('UPDATE employees SET name = :name, cpf = :cpf, base_salary = :base_salary, department = :department, position = :position, hire_date = :hire_date, termination_date = :termination_date WHERE id = :id');
        $statement->execute([
            'id' => $employee->getId(),
            'name' => $employee->getName(),
            'cpf' => $employee->getCpf(),
            'base_salary' => $employee->getBaseSalary(),
            'department' => $employee->getDepartment(),
            'position' => $employee->getPosition(),
            'hire_date' => $employee->getHireDate()->format('Y-m-d'),
            'termination_date' => $employee->getTerminationDate()?->format('Y-m-d'),
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM employees WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Employee
    {
        return new Employee(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['cpf'],
            (float) $row['base_salary'],
            (string) $row['department'],
            (string) $row['position'],
            new DateTimeImmutable((string) $row['hire_date']),
            isset($row['termination_date']) && $row['termination_date'] !== null && $row['termination_date'] !== ''
                ? new DateTimeImmutable((string) $row['termination_date'])
                : null
        );
    }
}
