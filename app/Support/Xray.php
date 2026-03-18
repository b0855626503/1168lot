<?php
// app/Support/Xray.php
namespace App\Support;

class Xray {
    public bool $active = false;
    /** @var array<int,array{sql:string,time:float}> */ public array $db = [];
    /** @var array<int,array{cmd:string,time:float}> */ public array $redis = [];
    /** @var array<int,array{uri:string,ms:int,code:int}> */ public array $http = [];

    public function reset(): void { $this->db = $this->redis = $this->http = []; }
    public function activate(): void { $this->active = true; $this->reset(); }
    public function deactivate(): void { $this->active = false; }
}
