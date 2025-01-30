<?php
require_once '../includes/config.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

// Get user ID from accounts table
$userID = $_SESSION['user_id']; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $char_name = trim($_POST['char_name']);
    $model = trim($_POST['model']);
    
    // Validasi format nama (First_Lastname)
    if (!preg_match('/^[A-Z][a-z]+_[A-Z][a-z]+$/', $char_name)) {
        $error = "Character name must be in format: First_Lastname";
    }
    // Validasi model (1-5 angka)
    elseif (!preg_match('/^\d{1,5}$/', $model)) {
        $error = "Skin ID must be between 1-5 digits";
    } else {
        // Generate random 6-digit phone number
        $phoneNumbr = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Set default values
        $online = 1;
        $tutorial = 1;
        $activated = 1;
        $phoneModel = 1;

        // Check if character name already exists
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM characters WHERE char_name = ?");
        $stmt->bind_param("s", $char_name);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        
        if ($count > 0) {
            $error = "Character name already exists!";
        } else {
            // Insert character using user's ID from accounts table
            $stmt = $conn->prepare("INSERT INTO characters (master, char_name, Online, Tutorial, Activated, Model, PhoneNumbr, PhoneModel) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isiiisii", $userID, $char_name, $online, $tutorial, $activated, $model, $phoneNumbr, $phoneModel);

            if ($stmt->execute()) {
                $success = "Character created successfully!";
            } else {
                $error = "Error creating character: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Get existing characters for this user using accounts ID
$characters = array();
$stmt = $conn->prepare("SELECT c.ID, c.char_name, c.Model, c.PhoneNumbr, c.Online, c.Tutorial, c.Activated, a.Username 
                       FROM characters c 
                       JOIN accounts a ON c.master = a.ID 
                       WHERE c.master = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($char_id, $char_name, $model, $phoneNumbr, $online, $tutorial, $activated, $username);

while ($stmt->fetch()) {
    $characters[] = array(
        'ID' => $char_id,
        'char_name' => $char_name,
        'Model' => $model,
        'PhoneNumbr' => $phoneNumbr,
        'Online' => $online,
        'Tutorial' => $tutorial,
        'Activated' => $activated,
        'Username' => $username
    );
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Character - Jawa System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Create Character specific styles */
        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            width: 100px;
            margin-bottom: 1.5rem;
        }

        .info-card {
            background: #334155;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            color: #94a3b8;
            font-size: 0.875rem;
        }

        .info-card strong {
            color: #f8fafc;
            font-weight: 500;
        }

        .model-info {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #60a5fa;
        }

        .model-info a {
            color: #60a5fa;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .model-info a:hover {
            color: #93c5fd;
            text-decoration: underline;
        }

        .character-list {
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid #334155;
        }

        .character-list h3 {
            color: #f8fafc;
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .character-card {
            background: #334155;
            padding: 1.25rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: transform 0.2s ease;
        }

        .character-card:hover {
            transform: translateY(-2px);
        }

        .character-card p {
            margin-bottom: 0.5rem;
            color: #94a3b8;
            font-size: 0.875rem;
        }

        .character-card strong {
            color: #f8fafc;
            font-weight: 500;
        }

        .status-badges {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            background: #1e293b;
            color: #94a3b8;
        }

        .status-badge.active {
            background: rgba(34, 197, 94, 0.1);
            color: #86efac;
        }

        input {
            background: #334155;
            border: 2px solid #475569;
            color: #f8fafc;
        }

        input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        input::placeholder {
            color: #64748b;
        }

        .buttons {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }

        .btn {
            flex: 1;
            text-align: center;
        }

        .btn-secondary {
            background: #475569;
        }

        .btn-secondary:hover {
            background: #334155;
        }

        @media (max-width: 640px) {
            .container {
                padding: 1.5rem;
                margin: 1rem;
            }

            .buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="../assets/images/logo.png" alt="Jawa System Logo" class="logo">
            <h2>Create New Character</h2>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="info-card">
            <p><strong>Account ID:</strong> <?php echo htmlspecialchars($userID); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="char_name">Character Name</label>
                <input type="text" id="char_name" name="char_name" required 
                       pattern="[A-Z][a-z]+_[A-Z][a-z]+" 
                       title="Format: First_Lastname (e.g., Naufal_Araja)"
                       placeholder="Naufal_Araja">
            </div>

            <div class="form-group">
                <label for="model">Skin ID</label>
                <input type="text" id="model" name="model" required 
                       pattern="\d{1,5}" 
                       title="Please enter between 1-5 digits"
                       placeholder="1-99999">
                <div class="model-info">
                    View available skin IDs: <a href="https://www.open.mp/docs/scripting/resources/skins" target="_blank">SA-MP Skin List</a>
                </div>
            </div>

            <div class="buttons">
                <button type="submit" class="btn btn-primary">Create Character</button>
                <a href="../pages/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>

        <?php if (!empty($characters)): ?>
        <div class="character-list">
            <h3>Your Characters</h3>
            <?php foreach ($characters as $character): ?>
            <div class="character-card">
                <p><strong>Character ID:</strong> <?php echo htmlspecialchars($character['ID']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($character['char_name']); ?></p>
                <p><strong>Skin ID:</strong> <?php echo htmlspecialchars($character['Model']); ?></p>
                <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($character['PhoneNumbr']); ?></p>
                <div class="status-badges">
                    <span class="status-badge <?php echo $character['Online'] ? 'active' : ''; ?>">
                        Online
                    </span>
                    <span class="status-badge <?php echo $character['Tutorial'] ? 'active' : ''; ?>">
                        Tutorial
                    </span>
                    <span class="status-badge <?php echo $character['Activated'] ? 'active' : ''; ?>">
                        Activated
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>