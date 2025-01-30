<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$userID = $_SESSION['user_id'];
$message = "";

// Handle character deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_character'])) {
    $char_id = $_POST['char_id'];
    $stmt = $conn->prepare("DELETE FROM characters WHERE ID = ? AND master = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $char_id, $userID);
        if ($stmt->execute()) {
            $message = "Character deleted successfully!";
        } else {
            $message = "Error deleting character.";
        }
        $stmt->close();
    }
}

// Handle skin ID update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_skin'])) {
    $char_id = $_POST['char_id'];
    $new_skin = intval($_POST['new_skin']);

    // Validate 5-digit input
    if ($new_skin >= 0 && $new_skin <= 99999) {
        $stmt = $conn->prepare("UPDATE characters SET Model = ? WHERE ID = ? AND master = ?");
        if ($stmt) {
            $stmt->bind_param("iii", $new_skin, $char_id, $userID);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = "Skin ID berhasil diupdate menjadi " . $new_skin;
                } else {
                    $message = "Tidak ada perubahan pada Skin ID";
                }
            } else {
                $message = "Error updating skin ID: " . $conn->error;
            }
            $stmt->close();
        }
    } else {
        $message = "Skin ID harus berada di antara 0 dan 99999";
    }
}

// Get factions using simple query
$factions = [];
$factionResult = $conn->query("SELECT factionID, factionName FROM factions");
if ($factionResult) {
    while ($row = $factionResult->fetch_assoc()) {
        $factions[$row['factionID']] = $row['factionName'];
    }
    $factionResult->free();
}

// Get characters using simple query with ORDER BY char_name
$characters = [];
$characterResult = $conn->query("SELECT ID, char_name, Model, PhoneNumbr, Faction, FactionRank, 
    Level, Exp, BankAccount, Cash, Savings, playerJob, playerSideJob, playerJobRank 
    FROM characters WHERE master = " . $conn->real_escape_string($userID) . " ORDER BY char_name ASC");

if ($characterResult) {
    while ($row = $characterResult->fetch_assoc()) {
        $characters[] = $row;
    }
    $characterResult->free();
}

function getFactionName($factionID, $factions)
{
    if ($factionID == -1) return 'Civilian';
    return isset($factions[$factionID]) ? $factions[$factionID] : 'Unknown';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Characters</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script>
        function validateSkinInput(input) {
            const value = parseInt(input.value);
            if (value < 0 || value > 99999) {
                input.setCustomValidity('Skin ID harus berada di antara 0 dan 99999');
            } else {
                input.setCustomValidity('');
            }
        }

        function formatSkinInput(input) {
            let value = input.value.replace(/\D/g, '');
            value = value.slice(0, 5);
            input.value = value;
        }
    </script>
</head>

<body>
    <div class="dash-container">
        <div class="dash-header">
            <img src="../assets/images/logo.png" alt="Jawa System Logo" class="dash-logo">
            <h1>Character List</h1>
            <small>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></small>
        </div>

        <?php if (!empty($message)): ?>
            <div class="dash-message <?php echo strpos(strtolower($message), 'error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($characters)): ?>
            <div class="dash-card">
                <h2>No Characters Found</h2>
                <p>You haven't created any characters yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($characters as $char): ?>
                <div class="dash-card">
                    <div class="dash-card-header">
                        <div class="dash-card-name"><?php echo htmlspecialchars($char['char_name']); ?></div>
                        <div class="dash-actions">
                            <form method="POST" class="dash-skin-form" onsubmit="return confirm('Update skin ID to ' + this.new_skin.value + '?');">
                                <input type="hidden" name="char_id" value="<?php echo $char['ID']; ?>">
                                <input type="number"
                                    name="new_skin"
                                    value="<?php echo htmlspecialchars($char['Model']); ?>"
                                    class="dash-skin-input"
                                    min="0"
                                    max="99999"
                                    oninput="formatSkinInput(this); validateSkinInput(this);"
                                    required>
                                <button type="submit" name="update_skin" class="dash-skin-btn">Update Skin</button>
                                <div class="dash-current-skin">
                                    Current: <?php echo htmlspecialchars($char['Model']); ?>
                                    <br>
                                    <a href="https://www.open.mp/docs/scripting/resources/skins" target="_blank" class="dash-skin-link">View Skin List</a>
                                </div>
                            </form>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this character?');">
                                <input type="hidden" name="char_id" value="<?php echo $char['ID']; ?>">
                                <button type="submit" name="delete_character" class="dash-delete-btn">Delete</button>
                            </form>
                        </div>
                    </div>
                    <div class="dash-stats">
                        <div class="dash-stat-group">
                            <div class="dash-stat-item">
                                <span class="dash-stat-label">Level</span>
                                <span class="dash-stat-value"><?php echo htmlspecialchars($char['Level']); ?></span>
                            </div>
                            <div class="dash-stat-item">
                                <span class="dash-stat-label">Experience</span>
                                <span class="dash-stat-value"><?php echo htmlspecialchars($char['Exp']); ?></span>
                            </div>
                            <div class="dash-stat-item">
                                <span class="dash-stat-label">Phone</span>
                                <span class="dash-stat-value"><?php echo htmlspecialchars($char['PhoneNumbr']); ?></span>
                            </div>
                        </div>
                        <div class="dash-stat-group">
                            <div class="dash-stat-item">
                                <span class="dash-stat-label">Faction</span>
                                <span class="dash-stat-value"><?php echo htmlspecialchars(getFactionName($char['Faction'], $factions)); ?></span>
                            </div>
                            <div class="dash-stat-item">
                                <span class="dash-stat-label">Job</span>
                                <span class="dash-stat-value"><?php echo $char['playerJob'] ? htmlspecialchars($char['playerJob']) : 'Unemployed'; ?></span>
                            </div>
                            <div class="dash-stat-item">
                                <span class="dash-stat-label">Side Job</span>
                                <span class="dash-stat-value"><?php echo $char['playerSideJob'] ? htmlspecialchars($char['playerSideJob']) : 'None'; ?></span>
                            </div>
                        </div>
                        <div class="dash-stat-group">
                            <div class="dash-stat-item">
                                <span class="dash-stat-label">Cash</span>
                                <span class="dash-stat-value">$<?php echo number_format($char['Cash']); ?></span>
                            </div>
                            <div class="dash-stat-item">
                                <span class="dash-stat-label">Bank</span>
                                <span class="dash-stat-value">$<?php echo number_format($char['BankAccount']); ?></span>
                            </div>
                            <div class="dash-stat-item">
                                <span class="dash-stat-label">Savings</span>
                                <span class="dash-stat-value">$<?php echo number_format($char['Savings']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="dash-buttons">
            <a href="../pages/create_character.php" class="dash-btn dash-btn-primary">Create New Character</a>
            <a href="../index.php" class="dash-btn dash-btn-danger">Logout</a>
        </div>
    </div>
</body>

</html>