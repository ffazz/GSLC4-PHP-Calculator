<?php
declare(strict_types=1);
session_start();

class Calculator {
    public static function add(float $a, float $b): float { return $a + $b; }
    public static function sub(float $a, float $b): float { return $a - $b; }
    public static function mul(float $a, float $b): float { return $a * $b; }
    public static function div(float $a, float $b) { return $b != 0 ? $a / $b : null; }
    public static function mod(float $a, float $b) { return $b != 0 ? fmod($a, $b) : null; }
    public static function pow(float $a, float $b): float { return pow($a, $b); }
    public static function sqrt(float $a) { return $a >= 0 ? sqrt($a) : null; }
    public static function factorial(int $n) { if ($n < 0) return null; $res = 1; for ($i = 1; $i <= $n; $i++) $res *= $i; return $res; }
    public static function gcd(int $a, int $b): int { $a = abs($a); $b = abs($b); while ($b) { $t = $b; $b = $a % $b; $a = $t; } return $a; }
    public static function lcm(int $a, int $b): int { if ($a === 0 || $b === 0) return 0; return (int)(abs($a * $b) / self::gcd($a, $b)); }
    public static function isPrime(int $n): bool { if ($n <= 1) return false; if ($n <= 3) return true; if ($n % 2 == 0 || $n % 3 == 0) return false; for ($i = 5; $i * $i <= $n; $i += 6) if ($n % $i == 0 || $n % ($i + 2) == 0) return false; return true; }
    public static function mean(array $values) { if (empty($values)) return null; return array_sum($values) / count($values); }
    public static function variance(array $values) { $m = self::mean($values); if ($m === null) return null; $sum = 0; foreach ($values as $v) $sum += ($v - $m) ** 2; return $sum / count($values); }
    public static function stddev(array $values) { $v = self::variance($values); return $v === null ? null : sqrt($v); }
}

class Converter {
    public static function celsiusToFahrenheit(float $c): float { return $c * 9/5 + 32; }
    public static function fahrenheitToCelsius(float $f): float { return ($f - 32) * 5/9; }
    public static function metersToFeet(float $m): float { return $m * 3.28084; }
    public static function feetToMeters(float $f): float { return $f / 3.28084; }
    public static function kgToLbs(float $kg): float { return $kg * 2.20462; }
    public static function lbsToKg(float $lbs): float { return $lbs / 2.20462; }
    public static function baseConvert(int $number, int $baseFrom, int $baseTo) {
        $asDec = intval(base_convert((string)$number, $baseFrom, 10));
        return base_convert((string)$asDec, 10, $baseTo);
    }
}

class SequenceGenerator {
    public static function fibonacci(int $n): array {
        if ($n <= 0) return [];
        $a = [0,1];
        if ($n === 1) return [0];
        if ($n === 2) return [$a[0], $a[1]];
        for ($i = 2; $i < $n; $i++) $a[] = $a[$i-1] + $a[$i-2];
        return array_slice($a, 0, $n);
    }
    public static function arithmetic(int $n, float $start, float $diff): array {
        $out = [];
        for ($i=0;$i<$n;$i++) $out[] = $start + $i * $diff;
        return $out;
    }
    public static function geometric(int $n, float $start, float $ratio): array {
        $out = [];
        for ($i=0;$i<$n;$i++) $out[] = $start * ($ratio ** $i);
        return $out;
    }
}

class History {
    private const KEY = 'calc_history';
    public static function push(array $entry) {
        if (!isset($_SESSION[self::KEY])) $_SESSION[self::KEY] = [];
        array_unshift($_SESSION[self::KEY], $entry);
        $_SESSION[self::KEY] = array_slice($_SESSION[self::KEY], 0, 200);
    }
    public static function all(): array { return $_SESSION[self::KEY] ?? []; }
    public static function clear() { unset($_SESSION[self::KEY]); }
    public static function exportCsv(): string {
        $rows = self::all();
        $out = fopen('php://memory', 'r+');
        fputcsv($out, ['time','feature','input','result']);
        foreach ($rows as $r) fputcsv($out, [$r['time'],$r['feature'],json_encode($r['input']),json_encode($r['result'])]);
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }
}

/* input handling */
$posted = $_POST;
$get = $_GET;
$feature = $posted['feature'] ?? $get['feature'] ?? null;
$result = null;
$error = null;

try {
    if ($feature === 'basic' && isset($posted['a'],$posted['b'],$posted['op'])) {
        $a = (float)$posted['a'];
        $b = (float)$posted['b'];
        switch ($posted['op']) {
            case 'add': $res = Calculator::add($a,$b); break;
            case 'sub': $res = Calculator::sub($a,$b); break;
            case 'mul': $res = Calculator::mul($a,$b); break;
            case 'div': $res = Calculator::div($a,$b); break;
            case 'mod': $res = Calculator::mod($a,$b); break;
            case 'pow': $res = Calculator::pow($a,$b); break;
            default: $res = null;
        }
        $result = $res;
        History::push(['time'=>date('c'),'feature'=>'Basic','input'=>['a'=>$a,'b'=>$b,'op'=>$posted['op']],'result'=>$res]);
    }

    if ($feature === 'scientific' && isset($posted['sop'],$posted['val'])) {
        $v = (float)$posted['val'];
        switch ($posted['sop']) {
            case 'sqrt': $res = Calculator::sqrt($v); break;
            case 'factorial': $res = Calculator::factorial((int)$v); break;
            default: $res = null;
        }
        $result = $res;
        History::push(['time'=>date('c'),'feature'=>'Scientific','input'=>['sop'=>$posted['sop'],'val'=>$v],'result'=>$res]);
    }

    if ($feature === 'converter' && isset($posted['ctype'],$posted['v'])) {
        $v = (float)$posted['v'];
        switch ($posted['ctype']) {
            case 'c2f': $res = Converter::celsiusToFahrenheit($v); break;
            case 'f2c': $res = Converter::fahrenheitToCelsius($v); break;
            case 'm2f': $res = Converter::metersToFeet($v); break;
            case 'f2m': $res = Converter::feetToMeters($v); break;
            default: $res = null;
        }
        $result = $res;
        History::push(['time'=>date('c'),'feature'=>'Converter','input'=>['type'=>$posted['ctype'],'val'=>$v],'result'=>$res]);
    }

    if ($feature === 'sequence' && isset($posted['stype'],$posted['n'])) {
        $n = (int)$posted['n'];
        switch ($posted['stype']) {
            case 'fib': $res = SequenceGenerator::fibonacci($n); break;
            case 'arith': $res = SequenceGenerator::arithmetic($n,(float)$posted['start'],(float)$posted['diff']); break;
            case 'geom': $res = SequenceGenerator::geometric($n,(float)$posted['start'],(float)$posted['ratio']); break;
            default: $res = null;
        }
        $result = $res;
        History::push(['time'=>date('c'),'feature'=>'Sequence','input'=>$posted,'result'=>$res]);
    }

    if ($feature === 'stats' && isset($posted['values'])) {
        $vals = array_map('floatval', preg_split('/[,\s]+/', trim($posted['values'])));
        $res = ['mean'=>Calculator::mean($vals),'variance'=>Calculator::variance($vals),'stddev'=>Calculator::stddev($vals)];
        $result = $res;
        History::push(['time'=>date('c'),'feature'=>'Stats','input'=>$vals,'result'=>$res]);
    }

    if ($feature === 'gcdlcm' && isset($posted['i1'],$posted['i2'])) {
        $i1 = (int)$posted['i1']; $i2 = (int)$posted['i2'];
        $res = ['gcd'=>Calculator::gcd($i1,$i2),'lcm'=>Calculator::lcm($i1,$i2)];
        $result = $res;
        History::push(['time'=>date('c'),'feature'=>'GCD/LCM','input'=>[$i1,$i2],'result'=>$res]);
    }

    if ($feature === 'prime' && isset($posted['p'])) {
        $p = (int)$posted['p'];
        $res = Calculator::isPrime($p);
        $result = $res;
        History::push(['time'=>date('c'),'feature'=>'Prime Check','input'=>['n'=>$p],'result'=>$res]);
    }

    if ($feature === 'export' && isset($get['action']) && $get['action'] === 'csv') {
        $csv = History::exportCsv();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="calc_history.csv"');
        echo $csv;
        exit;
    }

    if ($feature === 'clear_history') {
        History::clear();
        header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }

} catch (\Throwable $t) {
    $error = $t->getMessage();
}

/* small utilities */
function esc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$history = History::all();
$clockSeedColor = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Advanced PHP Calculator — GSLC</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f6f7fb; --card:#ffffff; --muted:#6b7280; --accent:#007aff; --glass:rgba(255,255,255,0.6);
}
*{box-sizing:border-box;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial}
body{margin:0;background:linear-gradient(180deg,#f7f8fc, #eef2ff);color:#0f172a;padding:28px;display:flex;justify-content:center}
.container{width:100%;max-width:1100px}
.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.brand{display:flex;gap:14px;align-items:center}
.logo{width:56px;height:56px;border-radius:12px;background:linear-gradient(135deg,var(--accent),#2dd4bf);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:18px;box-shadow:0 8px 24px rgba(2,6,23,0.08)}
.title{font-size:20px;font-weight:600}
.subtitle{color:var(--muted);font-size:13px}
.panel{display:grid;grid-template-columns:1fr 420px;gap:18px}
.card{background:var(--card);border-radius:16px;padding:18px;box-shadow:0 10px 30px rgba(2,6,23,0.06)}
.small{color:var(--muted);font-size:13px}
.field{display:flex;flex-direction:column;margin-bottom:10px}
.input{padding:10px 12px;border-radius:10px;border:1px solid #e6edf6;font-size:15px}
.row{display:flex;gap:10px}
.btn{background:var(--accent);color:white;padding:10px 12px;border-radius:10px;border:none;cursor:pointer}
.btn.ghost{background:transparent;color:var(--accent);border:1px solid #cfe3ff}
.tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.tab{padding:8px 12px;border-radius:999px;border:1px solid transparent;background:transparent;color:var(--muted);cursor:pointer}
.tab.active{background:linear-gradient(90deg,#fff,#f3f7ff);border-color:#e6efff;color:#0b1220;box-shadow:0 6px 18px rgba(2,6,23,0.04)}
.result{background:linear-gradient(180deg,#0b1220,#0f172a);color:white;padding:12px;border-radius:10px;font-weight:600}
.history-list{max-height:320px;overflow:auto}
.kv{display:flex;justify-content:space-between;padding:8px;border-bottom:1px dashed #f0f3ff;color:#334155;font-size:14px}
.clock{font-weight:600;color:var(--muted)}
.footer{display:flex;justify-content:space-between;margin-top:14px;color:var(--muted);font-size:13px}
.badge{background:#eef2ff;color:#0047b3;padding:6px 8px;border-radius:999px;font-size:12px}
.copy{cursor:pointer;color:#0b1220;text-decoration:underline}
.colorbox{height:56px;border-radius:12px}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.flex-col{display:flex;flex-direction:column}
.small-muted{font-size:12px;color:#94a3b8}
@media(max-width:900px){ .panel{grid-template-columns:1fr; } .header{flex-direction:column;align-items:flex-start;gap:10px}}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand">
      <div class="logo">AC</div>
      <div>
        <div class="title">Advanced Calculator</div>
        <div class="subtitle">OOP · PHP · Clean UI · Multiple features</div>
      </div>
    </div>
    <div style="text-align:right">
      <div class="clock" id="clock">--:--:--</div>
      <div class="small" id="date"><?= esc(date('l, j M Y')) ?></div>
    </div>
  </div>

  <div class="panel">
    <div>
      <div class="card" style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <div style="font-weight:600">Calculator Hub</div>
            <div class="small">Pilih mode dan masukkan nilai — semua operasi dicatat ke history</div>
          </div>
          <div style="text-align:right">
            <div class="small-muted">Theme color</div>
            <div class="colorbox" id="seed" style="background:<?= $clockSeedColor ?>"></div>
          </div>
        </div>

        <div style="margin-top:12px">
          <div class="tabs" id="tabs">
            <button class="tab active" data-tab="basic">Basic</button>
            <button class="tab" data-tab="scientific">Scientific</button>
            <button class="tab" data-tab="converter">Converter</button>
            <button class="tab" data-tab="sequence">Sequence</button>
            <button class="tab" data-tab="stats">Stats</button>
            <button class="tab" data-tab="gcd">GCD/LCM</button>
            <button class="tab" data-tab="prime">Prime</button>
          </div>

          <div id="content">
            <div class="tabpane" data-pane="basic">
              <form method="post" class="flex-col">
                <input type="hidden" name="feature" value="basic">
                <div class="row">
                  <input class="input" type="number" step="any" name="a" placeholder="Angka A" required>
                  <input class="input" type="number" step="any" name="b" placeholder="Angka B" required>
                </div>
                <div class="row" style="margin-top:8px">
                  <select class="input" name="op" required>
                    <option value="add">Add (+)</option>
                    <option value="sub">Subtract (-)</option>
                    <option value="mul">Multiply (×)</option>
                    <option value="div">Divide (÷)</option>
                    <option value="mod">Modulo (%)</option>
                    <option value="pow">Power (^)</option>
                  </select>
                  <button class="btn" type="submit">Compute</button>
                </div>
              </form>
            </div>

            <div class="tabpane" data-pane="scientific" style="display:none">
              <form method="post" class="flex-col">
                <input type="hidden" name="feature" value="scientific">
                <div class="row">
                  <select class="input" name="sop" required>
                    <option value="sqrt">Square Root</option>
                    <option value="factorial">Factorial (int)</option>
                  </select>
                  <input class="input" type="number" name="val" placeholder="Value" required>
                </div>
                <div style="margin-top:8px"><button class="btn" type="submit">Compute</button></div>
              </form>
            </div>

            <div class="tabpane" data-pane="converter" style="display:none">
              <form method="post" class="flex-col">
                <input type="hidden" name="feature" value="converter">
                <div class="row">
                  <select class="input" name="ctype" required>
                    <option value="c2f">Celsius → Fahrenheit</option>
                    <option value="f2c">Fahrenheit → Celsius</option>
                    <option value="m2f">Meters → Feet</option>
                    <option value="f2m">Feet → Meters</option>
                  </select>
                  <input class="input" type="number" step="any" name="v" placeholder="Value" required>
                </div>
                <div style="margin-top:8px"><button class="btn" type="submit">Convert</button></div>
              </form>
            </div>

            <div class="tabpane" data-pane="sequence" style="display:none">
              <form method="post" class="flex-col">
                <input type="hidden" name="feature" value="sequence">
                <div class="row">
                  <select class="input" name="stype" required>
                    <option value="fib">Fibonacci</option>
                    <option value="arith">Arithmetic Series</option>
                    <option value="geom">Geometric Series</option>
                  </select>
                  <input class="input" type="number" name="n" placeholder="Count (n)" required>
                </div>
                <div class="row" style="margin-top:8px">
                  <input class="input" type="number" step="any" name="start" placeholder="Start (for series)">
                  <input class="input" type="number" step="any" name="diff" placeholder="Diff / Ratio">
                </div>
                <div style="margin-top:8px"><button class="btn" type="submit">Generate</button></div>
              </form>
            </div>

            <div class="tabpane" data-pane="stats" style="display:none">
              <form method="post" class="flex-col">
                <input type="hidden" name="feature" value="stats">
                <div class="field">
                  <label class="small-muted">Comma or space separated numbers</label>
                  <input class="input" name="values" placeholder="e.g. 10, 20, 30 40" required>
                </div>
                <div><button class="btn" type="submit">Analyze</button></div>
              </form>
            </div>

            <div class="tabpane" data-pane="gcd" style="display:none">
              <form method="post" class="flex-col">
                <input type="hidden" name="feature" value="gcdlcm">
                <div class="row">
                  <input class="input" type="number" name="i1" placeholder="Integer 1" required>
                  <input class="input" type="number" name="i2" placeholder="Integer 2" required>
                </div>
                <div style="margin-top:8px"><button class="btn" type="submit">Compute</button></div>
              </form>
            </div>

            <div class="tabpane" data-pane="prime" style="display:none">
              <form method="post" class="flex-col">
                <input type="hidden" name="feature" value="prime">
                <div class="row">
                  <input class="input" type="number" name="p" placeholder="Check prime for integer" required>
                  <button class="btn" type="submit">Check</button>
                </div>
              </form>
            </div>

          </div>

          <div style="margin-top:12px">
            <?php if ($error): ?>
              <div class="result" style="background:#ff3b30">Error: <?= esc($error) ?></div>
            <?php elseif ($result !== null): ?>
              <div class="result">Result: <?= is_array($result) ? esc(json_encode($result)) : esc((string)$result) ?></div>
            <?php else: ?>
              <div class="small-muted">No computation yet. Gunakan panel di atas untuk memulai.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
          <div style="font-weight:600">History</div>
          <div style="display:flex;gap:8px;align-items:center">
            <a class="btn ghost" href="?feature=export&action=csv">Export CSV</a>
            <form method="post" style="display:inline">
              <input type="hidden" name="feature" value="clear_history">
              <button class="btn ghost" type="submit">Clear</button>
            </form>
          </div>
        </div>
        <div class="history-list">
          <?php if (empty($history)): ?>
            <div class="small-muted" style="padding:8px">History kosong</div>
          <?php else: foreach ($history as $h): ?>
            <div class="kv"><div style="max-width:70%"><strong><?= esc($h['feature']) ?></strong><div class="small-muted"><?= esc($h['time']) ?></div></div><div style="text-align:right"><div class="small-muted"><?= esc(is_array($h['result']) ? json_encode($h['result']) : (string)$h['result']) ?></div></div></div>
          <?php endforeach; endif; ?>
        </div>

        <div style="margin-top:12px;display:flex;gap:8px;align-items:center;justify-content:space-between">
          <div class="small-muted">Stored in PHP session · max 200 entries</div>
          <div><span class="badge"><?= count($history) ?></span></div>
        </div>
      </div>
    </div>

    <aside>
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <div style="font-weight:600">Quick Tools</div>
            <div class="small-muted">Utilities untuk tugas & demo</div>
          </div>
        </div>

        <div style="margin-top:12px" class="grid-2">
          <div>
            <div class="small-muted">Random theme</div>
            <button class="btn" id="randColor">Generate</button>
          </div>
          <div>
            <div class="small-muted">Copy last result</div>
            <button class="btn" id="copyLast">Copy</button>
          </div>
        </div>

        <div style="margin-top:12px">
          <div class="small-muted">Quick calculators</div>
          <div style="margin-top:8px" class="grid-2">
            <button class="btn" onclick="quick('bmi')">BMI</button>
            <button class="btn" onclick="quick('interest')">Interest</button>
          </div>
        </div>

        <div style="margin-top:12px">
          <div class="small-muted">Inspire</div>
          <div style="margin-top:8px;font-style:italic;color:#334155"><?= esc(['Simplicity is the ultimate sophistication.','Write code, ship features.','Design matters.'][array_rand([1,2,3])]) ?></div>
        </div>

      </div>

      <div class="card" style="margin-top:12px">
        <div style="font-weight:600;margin-bottom:8px">About</div>
        <div class="small-muted">This submission includes variables, data types, operators, looping, HTML, OOP structure and many integrated utilities — ready for cloud deployment.</div>
      </div>
    </aside>
  </div>

  <div class="footer">
    <div>Built with PHP · <?= esc(phpversion()) ?></div>
    <div>Advanced Calculator · GSLC</div>
  </div>
</div>

<script>
const tabs = document.querySelectorAll('.tab');
const panes = document.querySelectorAll('.tabpane');
tabs.forEach(t => t.addEventListener('click', ()=> {
  tabs.forEach(x=>x.classList.remove('active'));
  t.classList.add('active');
  panes.forEach(p=>p.style.display='none');
  document.querySelector('.tabpane[data-pane="'+t.dataset.tab+'"]').style.display='block';
}));
function updateClock(){document.getElementById('clock').innerText=new Date().toLocaleTimeString();}
setInterval(updateClock,1000); updateClock();
document.getElementById('randColor').addEventListener('click', ()=> {
  const c = '#'+Math.floor(Math.random()*16777215).toString(16).padStart(6,'0');
  document.getElementById('seed').style.background=c;
  document.querySelector(':root').style.setProperty('--accent', c);
});
document.getElementById('copyLast').addEventListener('click', ()=> {
  const el = document.querySelector('.result');
  if(!el){ alert('No result to copy'); return; }
  const txt = el.innerText.replace('Result: ','');
  navigator.clipboard.writeText(txt).then(()=> alert('Copied result'));
});
function quick(type){
  if(type==='bmi'){
    const w = prompt('Weight (kg):'); const h = prompt('Height (m):');
    if(w && h){ const bmi = (parseFloat(w)/ (parseFloat(h)*parseFloat(h))).toFixed(2); alert('BMI: '+bmi); }
  } else if(type==='interest'){
    const p = prompt('Principal:'); const r = prompt('Annual rate %:'); const t = prompt('Years:');
    if(p && r && t){ const val = (parseFloat(p) * Math.pow(1+parseFloat(r)/100, parseFloat(t))).toFixed(2); alert('Future value: '+val); }
  }
}
</script>
</body>
</html>
