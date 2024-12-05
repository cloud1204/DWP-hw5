<?php
session_start();

class BlackjackGame {
    private $deck;
    private $playerHand;
    private $dealerHand;
    private $currentChips;
    private $placedBet;
    private $history;
    private $round;

    public function __construct() {
        if (!isset($_SESSION['gameState'])) {
            $this->initGame();
        } else {
            $this->loadGameData();
        }
    }

    private function initGame() {
        $this->deck = [];
        $this->playerHand = [];
        $this->dealerHand = [];
        $this->currentChips = 1000;
        $this->placedBet = 0;
        $this->history = [];
        $this->round = 0;
        $this->createDeck();
        $this->saveGameData();
    }

    private function createDeck() {
        $suits = ['Heart', 'Diamond', 'Club', 'Spade'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        $this->deck = [];

        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $this->deck[] = ['suit' => $suit, 'value' => $value];
            }
        }
    }

    private function loadGameData() {
        $gameState = $_SESSION['gameState'];
        $this->deck = $gameState['deck'];
        $this->playerHand = $gameState['playerHand'];
        $this->dealerHand = $gameState['dealerHand'];
        $this->currentChips = $gameState['currentChips'];
        $this->placedBet = $gameState['placedBet'];
        $this->history = $gameState['history'];
        $this->round = $gameState['round'];
    }

    private function saveGameData() {
        $_SESSION['gameState'] = [
            'deck' => $this->deck,
            'playerHand' => $this->playerHand,
            'dealerHand' => $this->dealerHand,
            'currentChips' => $this->currentChips,
            'placedBet' => $this->placedBet,
            'history' => $this->history,
            'round' => $this->round,
        ];
    }

    private function drawCard($faceup = true) {
        if (empty($this->deck)) {
            $this->createDeck(); // Reshuffle the deck if empty
        }
        $cardIndex = array_rand($this->deck);
        $card = $this->deck[$cardIndex];
        unset($this->deck[$cardIndex]);
        return array_merge($card, ['faceup' => $faceup]);
    }

    private function calculateScore($hand) {
        $score = 0;
        $aceCount = 0;
        foreach ($hand as $card) {
            if (!$card['faceup']) {
                continue;
            } elseif (in_array($card['value'], ['J', 'Q', 'K'])) {
                $score += 10;
            } elseif ($card['value'] === 'A') {
                $score += 11;
                $aceCount++;
            } else {
                $score += intval($card['value']);
            }
        }
        while ($score > 21 && $aceCount > 0) {
            $score -= 10;
            $aceCount--;
        }
        return $score;
    }

    public function startRound() {
        if ($this->placedBet == 0) {
            echo "Place your bet first.\n";
            return;
        }

        $this->playerHand = [$this->drawCard(), $this->drawCard()];
        $this->dealerHand = [$this->drawCard(), $this->drawCard(false)];
        $this->saveGameData();
    }

    public function hit() {
        $this->playerHand[] = $this->drawCard();
        $this->saveGameData();

        if ($this->calculateScore($this->playerHand) >= 21) {
            $this->stand();
        }
    }

    public function stand() {
        while ($this->calculateScore($this->dealerHand) < 17) {
            $this->dealerHand[] = $this->drawCard();
        }

        $playerScore = $this->calculateScore($this->playerHand);
        $dealerScore = $this->calculateScore($this->dealerHand);

        if ($dealerScore > 21 || $playerScore > $dealerScore) {
            $this->endRound(1); // Player wins
        } elseif ($dealerScore == $playerScore) {
            $this->endRound(-1); // Tie
        } else {
            $this->endRound(0); // Dealer wins
        }
    }

    private function generateHandHTML($hand) {
        $cardsHTML = '';
        foreach ($hand as $index => $card) {
            $id = '';
            if ($card['value'] == '10') $id = '10';
            elseif ($card['value'] == 'A') $id = '01';
            elseif ($card['value'] == 'J') $id = '11';
            elseif ($card['value'] == 'Q') $id = '12';
            elseif ($card['value'] == 'K') $id = '13';
            else $id = sprintf('%02d', $card['value']);

            $cardImagePath = "cards/{$card['suit']}{$id}.png";
            $isFaceUp = $card['faceup'] ? 'flipped' : '';

            $cardsHTML .= "
                <div class=\"card-container\" data-card-index=\"{$index}\">
                    <div class=\"card {$isFaceUp}\">
                        <div class=\"card-front\">
                            <img src=\"cards/tmp2.png\" alt=\"Face Down Card\">
                        </div>
                        <div class=\"card-back\">
                            <img src=\"{$cardImagePath}\" alt=\"{$card['value']} of {$card['suit']}\">
                        </div>
                    </div>
                </div>";
        }

        $score = $this->calculateScore($hand);
        if (!$score) return "";
        $color = ($score > 21) ? '#ed0e02' : ($score == 21 ? '#a405e8' : ($score >= 15 ? '#f58e18' : ($score >= 10 ? '#e8c202' : '#09ab52')));

        $scoreCircle = "
            <div class=\"score-circle\" style=\"background-color: {$color}\">
                {$score}
            </div>";
        
        return $cardsHTML . $scoreCircle;
    }

    private function updateUI($win = 0) {
        $playerHandHTML = $this->generateHandHTML($this->playerHand);
        $dealerHandHTML = $this->generateHandHTML($this->dealerHand);

        $resultText = ($win == 1) ? "Player Wins!" : (($win == 0) ? "Dealer Wins!" : "Tie");

        echo json_encode([
            'playerHandHTML' => $playerHandHTML,
            'dealerHandHTML' => $dealerHandHTML,
            'currentChips' => $this->currentChips,
            'placedBet' => $this->placedBet,
            'result' => $resultText,
        ]);

        $this->saveToCookie();
        $this->saveToDatabase();
    }

    private function endRound($playerWon) {
        if ($playerWon == 1) {
            $this->currentChips += $this->placedBet * 2;
        } elseif ($playerWon == -1) {
            $this->currentChips += $this->placedBet;
        }

        $this->history[] = [
            'round' => ++$this->round,
            'bet' => $this->placedBet,
            'result' => $playerWon == 1 ? "Player Wins" : ($playerWon == 0 ? "Dealer Wins" : "Tie"),
            'chips' => $this->currentChips,
        ];

        if (count($this->history) > 10) {
            array_shift($this->history); // Keep history size to 10
        }

        $this->saveToCookie();
        //$this->saveToDatabase();
        $this->saveGameData();
    }

    private function saveToCookie() {
        $gameData = json_encode([
            'deck' => $this->deck,
            'playerHand' => $this->playerHand,
            'dealerHand' => $this->dealerHand,
            'currentChips' => $this->currentChips,
            'placedBet' => $this->placedBet,
            'history' => $this->history,
            'round' => $this->round,
        ]);

        // Store the game data in a cookie, valid for 7 days
        setcookie('blackjack_game', $gameData, time() + (7 * 24 * 60 * 60), '/');
    }

    private function saveToDatabase() {
        // Database connection (replace with your DB details)
        $pdo = new PDO('mysql:host=localhost;dbname=blackjack', 'username', 'password');

        $gameData = json_encode([
            'deck' => $this->deck,
            'playerHand' => $this->playerHand,
            'dealerHand' => $this->dealerHand,
            'currentChips' => $this->currentChips,
            'placedBet' => $this->placedBet,
            'history' => $this->history,
            'round' => $this->round,
        ]);

        // Insert game data into the database
        $stmt = $pdo->prepare("INSERT INTO game_data (player_id, game_state, created_at) VALUES (:player_id, :game_state, NOW())");
        $stmt->execute([
            ':player_id' => 1, // Replace with actual player ID if available
            ':game_state' => $gameData,
        ]);
    }

    public function placeBet($amount) {
        if ($amount > $this->currentChips || $amount <= 0) {
            echo "Invalid bet amount!";
            return false;
        }

        $this->placedBet = $amount;
        $this->currentChips -= $amount;
        $this->saveGameData();
        return true;
    }

    public function restartGame() {
        $this->initGame();
    }

    public function renderGameState() {
        echo "<h3>Current Chips: {$this->currentChips}</h3>";
        echo "<h4>Placed Bet: {$this->placedBet}</h4>";
        echo "<h5>Player Hand: " . json_encode($this->playerHand) . "</h5>";
        echo "<h5>Dealer Hand: " . json_encode($this->dealerHand) . "</h5>";
    }
}

$game = new BlackjackGame();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'placeBet':
                $game->placeBet((int)$_POST['bet']);
                break;
            case 'startRound':
                $game->startRound();
                break;
            case 'hit':
                $game->hit();
                break;
            case 'stand':
                $game->stand();
                break;
            case 'restart':
                $game->restartGame();
                break;
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Blackjack Game</title>
</head>
<body>
    <h1>Blackjack Game</h1>
    <form method="post">
        <input type="number" name="bet" placeholder="Bet Amount" required>
        <button type="submit" name="action" value="placeBet">Place Bet</button>
    </form>
    <form method="post">
        <button type="submit" name="action" value="startRound">Start Round</button>
        <button type="submit" name="action" value="hit">Hit</button>
        <button type="submit" name="action" value="stand">Stand</button>
        <button type="submit" name="action" value="restart">Restart Game</button>
    </form>

    <div>
        <?php $game->renderGameState(); ?>
    </div>
</body>
</html>
