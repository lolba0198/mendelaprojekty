<?php
$baza = new PDO("mysql:host=localhost;dbname=planer", "root", "");
$baza->query("SET NAMES utf8");

session_start();

if (isset($_GET['jezyk'])) {
    $_SESSION['jezyk'] = $_GET['jezyk'];
}

$jezyk = $_SESSION['jezyk'] ?? 'pl_PL';

$dostepne_jezyki = [
    'pl_PL' => 'Polski',
    'en_GB' => 'English',
    'de_DE' => 'Deutsch',
];

putenv("LANGUAGE=$jezyk");
putenv("LANG=$jezyk");
setlocale(LC_ALL, $jezyk . '.UTF-8', $jezyk . '.utf8', $jezyk);
bindtextdomain('planer', './locale');
bind_textdomain_codeset('planer', 'UTF-8');
textdomain('planer');

if ($_POST) {
    $data_tekst = $_POST['data'];
    $data_obiektu = new DateTime($data_tekst);
    
    if (isset($_POST['anuluj'])) {
        $miesiac = $data_obiektu->format('n');
        $rok     = $data_obiektu->format('Y'); 
        header("Location: planer.php?m=$miesiac&r=$rok");
        exit;
    }

    if (isset($_POST['usun'])) {
        $zap = $baza->prepare("DELETE FROM zadania WHERE dzien = ?");
        $zap->execute([$data_tekst]);
    }

    if (isset($_POST['zapisz'])) {
        $tresc_zadania = $_POST['tresc'];
        $baza->prepare("DELETE FROM zadania WHERE dzien = ?")->execute([$data_tekst]);
        $czy_puste = trim($tresc_zadania) == "";
        if (!$czy_puste) {
            $zap = $baza->prepare("INSERT INTO zadania (dzien, tekst) VALUES (?, ?)");
            $zap->execute([$data_tekst, $tresc_zadania]);
        }
    }

    $miesiac = $data_obiektu->format('n');
    $rok     = $data_obiektu->format('Y');
    header("Location: planer.php?m=$miesiac&r=$rok");
    exit;
}

$aktualny_miesiac = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');
$aktualny_rok     = isset($_GET['r']) ? (int)$_GET['r'] : (int)date('Y');
$dzien_do_edycji  = isset($_GET['edytuj']) ? $_GET['edytuj'] : "";

$dni_z_zadaniami = $baza->query("SELECT dzien FROM zadania")->fetchAll(PDO::FETCH_COLUMN);

$pierwszy_dzien_miesiaca = new DateTime();
$pierwszy_dzien_miesiaca->setDate($aktualny_rok, $aktualny_miesiac, 1);
$pierwszy_dzien_miesiaca->setTime(0, 0, 0);

$ile_dni_w_miesiacu = (int)$pierwszy_dzien_miesiaca->format('t');
$puste_komorki = (int)$pierwszy_dzien_miesiaca->format('N') - 1;

$naglowki_dni = [
    _("Pn"), _("Wt"), _("Śr"), _("Cz"), _("Pt"), _("So"), _("Nd")
];

$nazwy_miesiecy = [
    1  => _("styczeń"),
    2  => _("luty"),
    3  => _("marzec"),
    4  => _("kwiecień"),
    5  => _("maj"),
    6  => _("czerwiec"),
    7  => _("lipiec"),
    8  => _("sierpień"),
    9  => _("wrzesień"),
    10 => _("październik"),
    11 => _("listopad"),
    12 => _("grudzień")
];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            text-align: center; 
            font-family: sans-serif; 
        }

        table { 
            margin: auto; 
            border-collapse: collapse; 
        }

        td { 
            width: 40px; 
            height: 40px; 
            border: 1px solid #ccc; 
        }

        th { 
            background: #eee; 
            height: 30px; 
            border: 1px solid #ccc; 
        }

        a { 
            display: block; 
            width: 100%; height: 100%; 
            line-height: 40px; 
            text-decoration: none; 
            color: black; 
        }

        .weekend a { 
            color: red; 
        }

        .szary { 
            background: #ccc; 
        }

        td:hover { 
            background: yellow; 
        }

        textarea { 
            width: 400px; 
            height: 200px; 
        }
        
    </style>
</head>
<body>

<form method="GET" style="margin-bottom: 10px;">
    <?= _("Wybierz język") ?>:
    <select name="jezyk" onchange="this.form.submit()">
        <?php foreach ($dostepne_jezyki as $kod => $nazwa): ?>
            <option value="<?= $kod ?>" <?= $kod === $jezyk ? 'selected' : '' ?>>
                <?= $nazwa ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="m" value="<?= $aktualny_miesiac ?>">
    <input type="hidden" name="r" value="<?= $aktualny_rok ?>">
</form>

<?php if ($dzien_do_edycji != ""): ?>

    <?php
    $zap = $baza->prepare("SELECT tekst FROM zadania WHERE dzien = ?");
    $zap->execute([$dzien_do_edycji]);
    $stary_tekst = $zap->fetchColumn();
    ?>

    <h3><?= _("Dzień") ?>: <?= $dzien_do_edycji ?></h3>

    <form method="POST">
        <input type="hidden" name="data" value="<?= $dzien_do_edycji ?>">
        <textarea name="tresc"><?= htmlspecialchars($stary_tekst) ?></textarea><br><br>
        <button name="zapisz"><?= _("OK") ?></button>
        <button name="usun"><?= _("Usuń") ?></button>
        <button name="anuluj"><?= _("Anuluj") ?></button>
    </form>

<?php else: ?>

    <form method="GET">
        <select name="m">
            <?php foreach ($nazwy_miesiecy as $numer => $nazwa): ?>
                <option value="<?= $numer ?>" <?= $numer == $aktualny_miesiac ? 'selected' : '' ?>>
                    <?= htmlspecialchars($nazwa) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="r">
            <?php for ($rok = 2025; $rok <= 2030; $rok++): ?>
                <option value="<?= $rok ?>" <?= $rok == $aktualny_rok ? 'selected' : '' ?>>
                    <?= $rok ?>
                </option>
            <?php endfor; ?>
        </select>
        <button><?= _("OK") ?></button>
    </form>

    <br>

    <table>
        <tr>
            <?php foreach ($naglowki_dni as $naglowek): ?>
                <th><?= htmlspecialchars($naglowek) ?></th>
            <?php endforeach; ?>
        </tr>
        <tr>
        <?php
        for ($i = 0; $i < $puste_komorki; $i++) {
            echo "<td></td>";
        }

        for ($dzien = 1; $dzien <= $ile_dni_w_miesiacu; $dzien++) {
            $data_komorki = sprintf("%04d-%02d-%02d", $aktualny_rok, $aktualny_miesiac, $dzien);
            $ma_zadanie = in_array($data_komorki, $dni_z_zadaniami);
            $obiekt_dnia = new DateTime($data_komorki);
            $numer_dnia_tygodnia = (int)$obiekt_dnia->format('N'); 
            $weekend = $numer_dnia_tygodnia >= 6;
            $klasa_css = "";
            if ($ma_zadanie) $klasa_css .= "szary "; 
            if ($weekend) $klasa_css .= "weekend";
            echo "<td class='$klasa_css'>";
            echo "<a href='?edytuj=$data_komorki&m=$aktualny_miesiac&r=$aktualny_rok'>$dzien</a>";
            echo "</td>";
            $numer_komorki = $dzien + $puste_komorki;
            if (($numer_komorki % 7) == 0) {
                echo "</tr><tr>";
            }
        }
        ?>
        </tr>
    </table>

    <br>

    <p>
        <?php
        $dzis = date('Y-m-d');
        $zap = $baza->prepare("SELECT tekst FROM zadania WHERE dzien = ?");
        $zap->execute([$dzis]);
        $zadanie_na_dzis = $zap->fetchColumn();

        if ($zadanie_na_dzis) {
            echo "<strong>" . _("Dzisiejsze plany") . ":</strong> " . htmlspecialchars($zadanie_na_dzis);
        } else {
            echo _("Nie masz planów na dziś");
        }
        ?>
    </p>

<?php endif; ?>

</body>
</html>