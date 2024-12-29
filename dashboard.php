<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['user_id'];
$message = "";

// Handle character deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_character'])) {
    $char_id = $_POST['char_id'];

    // Verify character belongs to user before deletion
    $stmt = $conn->prepare("DELETE FROM characters WHERE ID = ? AND master = ?");
    $stmt->bind_param("ii", $char_id, $userID);

    if ($stmt->execute()) {
        $message = "Character deleted successfully!";
    } else {
        $message = "Error deleting character.";
    }
    $stmt->close();
}

// Get factions
$factions = [];
$stmt = $conn->prepare("SELECT factionID, factionName FROM factions");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $factions[$row['factionID']] = $row['factionName'];
}
$stmt->close();

// Get characters data
$stmt = $conn->prepare("SELECT 
    ID, char_name, Model, PhoneNumbr, Faction, FactionRank, 
    Level, Exp, BankAccount, Cash, Savings,
    playerJob, playerSideJob, playerJobRank
    FROM characters 
    WHERE master = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$characters = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
    <link rel="stylesheet" href="style.css">
    <style>
        /* Dashboard specific styles */
        .container {
            max-width: 1200px;
            padding: 2rem;
            width: 95%;
            background: transparent;
            box-shadow: none;
        }

        .header {
            background: #1e293b;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header .logo {
            width: 290px;
            margin-bottom: 1rem;
        }

        .header h1 {
            color: #f8fafc;
            font-size: 1.5rem;
            margin: 0;
        }

        .header small {
            color: #94a3b8;
        }

        .message {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #86efac;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .character-card {
            background: #1e293b;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .character-card:hover {
            transform: translateY(-2px);
        }

        .character-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #334155;
        }

        .character-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #f8fafc;
        }

        .delete-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .delete-btn:hover {
            background: #dc2626;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .stat-group {
            padding: 1rem;
            background: #334155;
            border-radius: 8px;
        }

        .stat-item {
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-item:last-child {
            margin-bottom: 0;
        }

        .stat-label {
            color: #94a3b8;
            font-weight: 500;
        }

        .stat-value {
            color: #f8fafc;
            font-weight: 500;
        }

        .buttons {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .no-characters {
            text-align: center;
            padding: 3rem;
            background: #1e293b;
            border-radius: 12px;
            color: #94a3b8;
        }

        .no-characters h2 {
            color: #f8fafc;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .stats {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="assets/logo.png" alt="Jawa System Logo" class="logo">
            <h1>Character List</h1>
            <small>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></small>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (empty($characters)): ?>
            <div class="no-characters">
                <h2>No Characters Found</h2>
                <p>You haven't created any characters yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($characters as $char): ?>
                <div class="character-card">
                    <div class="character-header">
                        <div class="character-name">
                            <?php echo htmlspecialchars($char['char_name']); ?>
                        </div>
                        <form method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this character?');">
                            <input type="hidden" name="char_id" value="<?php echo $char['ID']; ?>">
                            <button type="submit" name="delete_character" class="delete-btn">Delete</button>
                        </form>
                    </div>
                    <div class="stats">
                        <div class="stat-group">
                            <div class="stat-item">
                                <span class="stat-label">Level</span>
                                <span class="stat-value"><?php echo htmlspecialchars($char['Level']); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Experience</span>
                                <span class="stat-value"><?php echo htmlspecialchars($char['Exp']); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Phone</span>
                                <span class="stat-value"><?php echo htmlspecialchars($char['PhoneNumbr']); ?></span>
                            </div>
                        </div>
                        <div class="stat-group">
                            <div class="stat-item">
                                <span class="stat-label">Faction</span>
                                <span class="stat-value"><?php echo htmlspecialchars(getFactionName($char['Faction'], $factions)); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Job</span>
                                <span class="stat-value"><?php echo $char['playerJob'] ? htmlspecialchars($char['playerJob']) : 'Unemployed'; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Side Job</span>
                                <span class="stat-value"><?php echo $char['playerSideJob'] ? htmlspecialchars($char['playerSideJob']) : 'None'; ?></span>
                            </div>
                        </div>
                        <div class="stat-group">
                            <div class="stat-item">
                                <span class="stat-label">Cash</span>
                                <span class="stat-value">$<?php echo number_format($char['Cash']); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Bank</span>
                                <span class="stat-value">$<?php echo number_format($char['BankAccount']); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Savings</span>
                                <span class="stat-value">$<?php echo number_format($char['Savings']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="buttons">
            <a href="create_character.php" class="btn btn-primary">Create New Character</a>
            <a href="login.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</body>

</html>