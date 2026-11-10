<?php

class Dukan
{
    public function __construct(string $name)
    {
        if ($name === '') {
            throw new InvalidArgumentException('Dukan ka naam khali nahi ho sakta');
        }

        if (strlen($name) < 3) {
            throw new InvalidArgumentException('Dukan ka naam bhara hai ');
        }
        echo 'Dukan ka naam: '.$name."\n";
    }
}

new Dukan('Noor Fabrics');
new Dukan('Zimal Store');
new Dukan('abc');

echo "AAKHRI LINE\n";