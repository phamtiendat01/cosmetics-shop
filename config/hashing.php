<?php
return [
    'driver' => extension_loaded('sodium') ? 'argon2id' : 'bcrypt',
    'bcrypt' => ['rounds' => 12], // >=12
    'argon'  => ['memory' => 65536, 'threads' => 2, 'time' => 4],
];
