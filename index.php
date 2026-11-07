<?php

$host = "localhost";
$port = 3307;
$dbname = "lab02_library";
$username = "root";
$password = "";

$errors = [];
$success = "";

$student_id = "";
$isbn = "";
$book_title = "";
$borrow_date = "";
$due_date = "";
$loan_duration = "";
$borrowing_purpose = "";
$agree = false;

// Check ISBN-10 and ISBN-13
function validISBN($isbn)
{
    $isbn = str_replace(["-", " "], "", $isbn);

    if (preg_match('/^\d{9}[\dX]$/', $isbn)) {
        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $value = ($isbn[$i] === 'X') ? 10 : (int)$isbn[$i];
            $sum += $value * (10 - $i);
        }

        return $sum % 11 === 0;
    }

    if (preg_match('/^\d{13}$/', $isbn)) {
        $sum = 0;

        for ($i = 0; $i < 13; $i++) {
            $sum += (int)$isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return $sum % 10 === 0;
    }

    return false;
}

// Connect to database
try {
    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database connection failed. Please check your database settings.");
}

// Process form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $student_id = trim($_POST["student_id"] ?? "");
    $isbn = trim($_POST["isbn"] ?? "");
    $book_title = trim($_POST["book_title"] ?? "");
    $borrow_date = trim($_POST["borrow_date"] ?? "");
    $due_date = trim($_POST["due_date"] ?? "");
    $loan_duration = trim($_POST["loan_duration"] ?? "");
    $borrowing_purpose = trim($_POST["borrowing_purpose"] ?? "");
    $agree = isset($_POST["agree"]);

    // Student ID validation
    if ($student_id === "") {
        $errors["student_id"] = "Student ID is required.";
    }

    // ISBN validation
    if ($isbn === "") {
        $errors["isbn"] = "Book ISBN is required.";
    } elseif (!validISBN($isbn)) {
        $errors["isbn"] = "Enter a valid ISBN-10 or ISBN-13.";
    }

    // Book title validation
    if ($book_title === "") {
        $errors["book_title"] = "Book title is required.";
    }

    // Borrow date validation
    if ($borrow_date === "") {
        $errors["borrow_date"] = "Borrow date is required.";
    } else {
        $borrowObject = DateTime::createFromFormat('!Y-m-d', $borrow_date);

        if (!$borrowObject || $borrowObject->format('Y-m-d') !== $borrow_date) {
            $errors["borrow_date"] = "Enter a valid borrow date.";
        } elseif ($borrow_date > date("Y-m-d")) {
            $errors["borrow_date"] = "Borrow date cannot be in the future.";
        }
    }

    // Due date validation
    if ($due_date === "") {
        $errors["due_date"] = "Return due date is required.";
    } else {
        $dueObject = DateTime::createFromFormat('!Y-m-d', $due_date);

        if (!$dueObject || $dueObject->format('Y-m-d') !== $due_date) {
            $errors["due_date"] = "Enter a valid due date.";
        } elseif ($borrow_date !== "" && $due_date <= $borrow_date) {
            $errors["due_date"] = "Due date must be after borrow date.";
        }
    }

    // Loan duration validation
    if ($loan_duration === "") {
        $errors["loan_duration"] = "Loan duration is required.";
    } elseif (!ctype_digit($loan_duration) || (int)$loan_duration < 1 || (int)$loan_duration > 30) {
        $errors["loan_duration"] = "Duration must be between 1 and 30 days.";
    }

    // Agreement validation
    if (!$agree) {
        $errors["agree"] = "You must agree to the library rules.";
    }

    // Save valid loan
    if (empty($errors)) {

        try {

            // Check for an existing active loan
            $check = $conn->prepare(
                "SELECT id FROM loans
                 WHERE student_id = ? AND isbn = ? AND status = 'Active'"
            );

            $check->execute([$student_id, $isbn]);

            if ($check->fetch()) {

                $errors["duplicate"] = "This student already has an active loan for this book.";

            } else {

                // Insert loan
                $sql = "INSERT INTO loans
                (student_id, isbn, book_title, borrow_date, due_date,
                loan_duration, borrowing_purpose)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    $student_id,
                    str_replace(["-", " "], "", $isbn),
                    $book_title,
                    $borrow_date,
                    $due_date,
                    $loan_duration,
                    $borrowing_purpose
                ]);

                $success = "Library loan request submitted successfully. Loan ID: " . $conn->lastInsertId();

                // Clear form
                $student_id = "";
                $isbn = "";
                $book_title = "";
                $borrow_date = "";
                $due_date = "";
                $loan_duration = "";
                $borrowing_purpose = "";
                $agree = false;
            }

        } catch (PDOException $e) {
            $errors["database"] = "Could not save the loan request. Please try again.";
        }
    }
}

// Safely display values
function clean($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Library Book Borrowing</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h1>Library Book Borrowing</h1>

    <p class="subtitle">Complete the form to request a library book.</p>

    <?php if ($success): ?>
        <div class="success">
            <?= clean($success) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($errors["duplicate"])): ?>
        <div class="error">
            <?= clean($errors["duplicate"]) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($errors["database"])): ?>
        <div class="error">
            <?= clean($errors["database"]) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">

        <label for="student_id">Student ID</label>
        <input type="text" id="student_id" name="student_id"
               value="<?= clean($student_id) ?>">

        <span class="field-error"><?= $errors["student_id"] ?? "" ?></span>

        <label for="isbn">Book ISBN</label>
        <input type="text" id="isbn" name="isbn"
               value="<?= clean($isbn) ?>">

        <span class="field-error"><?= $errors["isbn"] ?? "" ?></span>

        <label for="book_title">Book Title</label>
        <input type="text" id="book_title" name="book_title"
               value="<?= clean($book_title) ?>">

        <span class="field-error"><?= $errors["book_title"] ?? "" ?></span>

        <label for="borrow_date">Borrow Date</label>
        <input type="date" id="borrow_date" name="borrow_date"
               value="<?= clean($borrow_date) ?>">

        <span class="field-error"><?= $errors["borrow_date"] ?? "" ?></span>

        <label for="due_date">Return Due Date</label>
        <input type="date" id="due_date" name="due_date"
               value="<?= clean($due_date) ?>">

        <span class="field-error"><?= $errors["due_date"] ?? "" ?></span>

        <label for="loan_duration">Loan Duration (Days)</label>
        <input type="number" id="loan_duration" name="loan_duration"
               min="1" max="30"
               value="<?= clean($loan_duration) ?>">

        <span class="field-error"><?= $errors["loan_duration"] ?? "" ?></span>

        <label for="borrowing_purpose">Borrowing Purpose</label>
        <textarea id="borrowing_purpose" name="borrowing_purpose"><?= clean($borrowing_purpose) ?></textarea>

        <label class="checkbox">
            <input type="checkbox" name="agree" value="1"
                <?= $agree ? "checked" : "" ?>>
            I agree to the library rules.
        </label>

        <span class="field-error"><?= $errors["agree"] ?? "" ?></span>

        <div class="buttons">
            <button type="submit">Submit Request</button>
            <button type="reset" class="reset">Reset</button>
        </div>

    </form>

</div>

</body>
</html>