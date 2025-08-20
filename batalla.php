#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Simulación de batalla: Deadpool vs Wolverine
 * Requisitos del InstructivoAA1:
 * - El usuario define la vida inicial de cada protagonista.
 * - Daño aleatorio: Deadpool 10–100, Wolverine 10–120.
 * - Si se recibe daño MÁXIMO, el receptor NO ataca en su próximo turno (aturdido).
 * - Deadpool evade 25%, Wolverine evade 20%.
 * - Pierde quien llega a 0 o menos de vida.
 * - Mostrar lo que pasa en cada turno, la vida, resultado final y pausa de 1 segundo entre turnos.
 * Enfoque POO: clases, encapsulamiento, herencia.
 */

class Fighter {
    protected string $name;
    protected int $hp;
    protected int $minDamage;
    protected int $maxDamage;
    protected float $evadeChance; // 0.25 = 25%
    protected bool $stunned = false;

    public function __construct(string $name, int $hp, int $minDamage, int $maxDamage, float $evadeChance) {
        $this->name = $name;
        $this->hp = $hp;
        $this->minDamage = $minDamage;
        $this->maxDamage = $maxDamage;
        $this->evadeChance = $evadeChance;
    }

    public function getName(): string { return $this->name; }
    public function getHp(): int { return $this->hp; }
    public function isAlive(): bool { return $this->hp > 0; }
    public function isStunned(): bool { return $this->stunned; }
    public function clearStun(): void { $this->stunned = false; }

    protected function rollDamage(): int {
        return random_int($this->minDamage, $this->maxDamage);
    }

    protected function willEvade(): bool {
        // Probabilidad uniforme [0,1)
        $r = random_int(0, 1000000) / 1000000;
        return $r < $this->evadeChance;
    }

    public function receiveDamage(int $amount): bool {
        // Devuelve true si evade
        if ($this->willEvade()) {
            return true;
        }
        $this->hp -= $amount;
        return false;
    }

    public function attack(Fighter $opponent): array {
        // Retorna ['evaded'=>bool,'damage'=>int,'isMax'=>bool]
        $damage = $this->rollDamage();
        $evaded = $opponent->receiveDamage($damage);
        $isMax = ($damage === $this->maxDamage);
        if (!$evaded && $isMax && $opponent->isAlive()) {
            // El receptor queda aturdido: pierde su próximo turno
            $opponent->stunned = true;
        }
        return ['evaded' => $evaded, 'damage' => $damage, 'isMax' => $isMax];
    }
}

class Deadpool extends Fighter {
    public function __construct(int $hp) {
        parent::__construct('Deadpool', $hp, 10, 100, 0.25);
    }
}

class Wolverine extends Fighter {
    public function __construct(int $hp) {
        parent::__construct('Wolverine', $hp, 10, 120, 0.20);
    }
}

class Battle {
    private Fighter $a;
    private Fighter $b;
    private int $turn = 1;

    public function __construct(Fighter $a, Fighter $b) {
        $this->a = $a;
        $this->b = $b;
    }

    private function printStatus(): void {
        echo sprintf("HP => %s: %d | %s: %d\n", $this->a->getName(), $this->a->getHp(), $this->b->getName(), $this->b->getHp());
    }

    private function processTurn(Fighter $attacker, Fighter $defender): void {
        echo "Turno {$this->turn}: {$attacker->getName()} ataca a {$defender->getName()}...\n";
        if ($attacker->isStunned()) {
            echo "⏳ {$attacker->getName()} está aturdido y pierde su turno.\n";
            $attacker->clearStun();
            $this->printStatus();
            return;
        }
        $result = $attacker->attack($defender);
        if ($result['evaded']) {
            echo "🛡️ {$defender->getName()} evadió el ataque.\n";
        } else {
            echo "💥 Daño: {$result['damage']} a {$defender->getName()}";
            if ($result['isMax']) {
                echo " (¡daño máximo! {$defender->getName()} perderá su próximo turno)";
            }
            echo ".\n";
        }
        $this->printStatus();
    }

    public function run(): void {
        echo "=== COMIENZA LA BATALLA ===\n";
        $this->printStatus();
        while ($this->a->isAlive() && $this->b->isAlive()) {
            $this->processTurn($this->a, $this->b);
            if (!$this->b->isAlive()) break;
            sleep(1); // pausa entre turnos

            $this->turn++;
            $this->processTurn($this->b, $this->a);
            $this->turn++;

            if ($this->a->isAlive() && $this->b->isAlive()) {
                sleep(1);
            }
        }
        echo "=== RESULTADO FINAL ===\n";
        if ($this->a->isAlive() && !$this->b->isAlive()) {
            echo $this->a->getName() . " gana. " . $this->b->getName() . " cayó a 0 o menos HP.\n";
        } elseif (!$this->a->isAlive() && $this->b->isAlive()) {
            echo $this->b->getName() . " gana. " . $this->a->getName() . " cayó a 0 o menos HP.\n";
        } else {
            echo "Empate: ambos cayeron.\n";
        }
        $this->printStatus();
    }
}

function readPositiveInt(string $prompt): int {
    while (true) {
        $line = readline($prompt);
        if ($line === false) {
            throw new RuntimeException("Entrada finalizada");
        }
        $line = trim($line);
        if (ctype_digit($line) && (int)$line > 0) {
            return (int)$line;
        }
        echo "Por favor, ingresa un número entero positivo.\n";
    }
}

// Entrada del usuario
$hpDeadpool = readPositiveInt("Vida inicial de Deadpool: ");
$hpWolverine = readPositiveInt("Vida inicial de Wolverine: ");

// Instancias y batalla
$deadpool = new Deadpool($hpDeadpool);
$wolverine = new Wolverine($hpWolverine);
$battle = new Battle($deadpool, $wolverine);
$battle->run();
