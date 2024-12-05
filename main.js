/*
111550097 蔣昀成 第4次作業 11/17
111550097 Yun-Cheng Chiang The Fourth Homework 11/17
*/
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
class BlackjackGame {
    constructor() {
        this.deck = [];
        this.playerHand = [];
        this.dealerHand = [];
        this.currentChips = 1000;
        this.placedBet = 0;
        this.initGame();
        this.busy = false;
        this.isRoundOver = true;
        this.lock();
        this.history = []; // Store the game history results
        this.round = 0; // Keep track of rounds
    }

    lock(){
        if (this.busy) return false;

        document.getElementById("hit-button").classList.add("locked");
        document.getElementById("stand-button").classList.add("locked");

        this.busy = true;
        return true;
    }
    unlock(){
        this.busy = false;

        document.getElementById("hit-button").classList.remove("locked");
        document.getElementById("stand-button").classList.remove("locked");
    }

    initGame() {
        this.loadGameData();
        this.createDeck();
        this.updateUI();
        this.updateHistoryTable();
        console.log("inited");    
    }

    createDeck() {
        const suits = ['Heart', 'Diamond', 'Club', 'Spade'];
        const values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        this.deck = suits.flatMap(suit => values.map(value => ({ suit, value })));
    }

    loadGameData() {
        const data = JSON.parse(localStorage.getItem('blackjackData')) || 
                        JSON.parse(sessionStorage.getItem('blackjackData')) || [];
        if (data) {
            this.gameData = data.gameData || [];
            this.history = data.history || [];
            this.round = data.round || 0;
            this.currentChips = data.currentChips || 1000;
            console.log("yo!", data.round);
        } else {
            this.gameData = [];
            this.history = [];
            this.round = 0;
            this.currentChips = 1000;
        }
    }

    saveGameData(reset = false) {
        const dataToSave = {
            gameData: this.gameData,
            history: this.history,
            round: this.round,
            currentChips: this.currentChips,
        };
        if (reset){
            dataToSave.history = [];
            dataToSave.round = 0;
            dataToSave.currentChips = 1000;
        }
        console.log("ya!", dataToSave.round);
        localStorage.setItem('blackjackData', JSON.stringify(dataToSave));
        sessionStorage.setItem('blackjackData', JSON.stringify(dataToSave));
    }

    async flipCard(handContainer, cardIndex, isFaceUp) {
        const cardContainer = handContainer.querySelector(`[data-card-index="${cardIndex}"]`);
        if (cardContainer) {
            const card = cardContainer.querySelector('.card');
            if (isFaceUp) {
                card.classList.add('flipped'); // Add the flipped class to show the face-up side
            } else {
                card.classList.remove('flipped'); // Remove the flipped class to show the face-down side
            }
        } else {
            console.error(`Card with index ${cardIndex} not found in the specified hand container.`);
        }
    }

    revealDealerCards() {
        const dealerHandDiv = document.getElementById("dealer-hand");
        this.dealerHand.forEach((card, index) => {
            card.faceup = true; // Update the card state
            this.flipCard(dealerHandDiv, index, true); // Flip each card face-up
        });
    }

    startRound() {
        if (!this.isRoundOver) return;

        if (this.placedBet == 0){
            this.showBetModal();
            return;
        }

        document.getElementById("start-button").classList.add("locked");

        this.isRoundOver = false;
        document.getElementById("result").textContent = "-----------";

        this.playerHand = [this.drawCard(), this.drawCard()];
        this.dealerHand = [this.drawCard(), this.drawCard(false)];
        this.updateUI();
        console.log("starting");

        this.unlock();
    }

    drawCard(faceup = true) {
        if (this.deck.length == 0){
            this.createDeck(); // reshuffle the deck when used all
        }
        const cardIndex = Math.floor(Math.random() * this.deck.length);
        return {...this.deck.splice(cardIndex, 1)[0], faceup};
    }

    calculateScore(hand) {
        let score = 0;
        let aceCount = 0;
        hand.forEach(card => {
            if (!card.faceup) score += 0; //
            else if (['J', 'Q', 'K'].includes(card.value)) score += 10;
            else if (card.value === 'A') { score += 11; aceCount += 1; }
            else score += parseInt(card.value);
        });
        while (score > 21 && aceCount > 0) { score -= 10; aceCount--; }
        return score;
    }

    hit() {
        if (!this.lock()) return;

        this.playerHand.push(this.drawCard());
        this.updateUI();
        
        if (this.calculateScore(this.playerHand) >= 21) {
            this.unlock();
            this.stand();
            return;
        }

        this.unlock();
    }

    async stand() {
        if (!this.lock()) return;
        
        document.getElementById("result").textContent = "....Pending....";

        this.updateUI();
        await sleep(1000); 
        this.revealDealerCards();
        await sleep(1000);
        while (this.calculateScore(this.dealerHand) < 17) {
            this.dealerHand.push(this.drawCard(false));
            this.updateUI();
            await sleep(1000); 
            this.revealDealerCards();
            await sleep(1000);
        }

        const playerScore = this.calculateScore(this.playerHand);
        const dealerScore = this.calculateScore(this.dealerHand);

        console.log("players:", playerScore);
        console.log("dealers:", dealerScore);

        var win = 0;
        if (dealerScore > 21 && playerScore > 21) win = -1;
        else if (dealerScore == playerScore) win = -1;
        else win = dealerScore > 21 || (playerScore > dealerScore && playerScore <= 21);
        console.log("win:", win);
        this.endRound(win);

        //this.unlock();
    }
    endRound(playerWon) {
        this.isRoundOver = true;
        if (playerWon == 1) this.currentChips += this.placedBet * 2;
        else if (playerWon == -1) this.currentChips += this.placedBet;
        const bet = this.placedBet;
        this.placedBet = 0;
        this.updateUI(playerWon);
        const winner = (playerWon == 1 ? "Player" : (playerWon == 0 ? "Dealer" : "Tie"));
        console.log("Round Ends! Winner:", winner);

        document.getElementById("start-button").classList.remove("locked");

        this.round++;
        const roundResult = {
            round: this.round,
            bet: bet,
            result: winner,
            chips: this.currentChips
        };
        this.history.push(roundResult);
        if (this.history.length >= 11) {
            this.history.shift();
        }
        // Update the history table
        this.updateHistoryTable();

        this.gameData.push({ playerHand: this.playerHand, dealerHand: this.dealerHand, result: playerWon, chipsLeft: this.currentChips});
        this.saveGameData();
    }
    generateHandHTML(hand) {
        const cards = hand.map((card, index) => {
            var id = "";
            if (card.value == "10") id="10";
            else if (card.value == 'A') id = "01";
            else if (card.value == 'J') id = "11";
            else if (card.value == 'Q') id = "12";
            else if (card.value == 'K') id = "13";
            else id = `0${card.value}`;
            const cardImagePath = `cards/${card.suit}${id}.png`;
            const isFaceUp = card.faceup ? 'flipped' : '';
            return `
                <div class="card-container" data-card-index="${index}">
                    <div class="card ${isFaceUp}">
                        <div class="card-front">
                            <img src="cards/tmp2.png" alt="Face Down Card">
                        </div>
                        <div class="card-back">
                            <img src="${cardImagePath}" alt="${card.value} of ${card.suit}">
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        const score = this.calculateScore(hand);
        if (!score) return "";
        var color = "#09ab52";
        if (score >= 10) color = "#e8c202";
        if (score >= 15) color = "#f58e18";
        if (score == 21) color = "#a405e8";
        if (score > 21) color = "#ed0e02";

        const score_circle = `
            <div class="score-circle" style="background-color: ${color}"> ${this.calculateScore(hand)}</div>
        `;
        return cards + score_circle;
    }
    updateUI(win = 0) {
        // Display game state, chips, and cards in HTML (implement this in HTML)
        // Display player and dealer hands

        const playerHandDiv = document.getElementById("player-hand");
        const dealerHandDiv = document.getElementById("dealer-hand");

        playerHandDiv.innerHTML = this.generateHandHTML(this.playerHand);
        dealerHandDiv.innerHTML = this.generateHandHTML(this.dealerHand);

        // Update chip count and bet information
        document.getElementById("player-chips").textContent = this.currentChips;
        document.getElementById("result").textContent = "Placed Bet: " + this.placedBet;
        // If the round is over, display results
        if (this.isRoundOver) {
            const playerScore = this.calculateScore(this.playerHand);
            const dealerScore = this.calculateScore(this.dealerHand);
            const resultText = win == 1 ? "Player Wins!" : (win == 0 ? "Dealer Wins!" : "Tie");
            document.getElementById("result").textContent = resultText;
            console.log("result:", resultText);
        }
    }

    showBetModal() {

        if (this.currentChips == 0){
            alert("GG! You are broke. Click the restart button to start a new game.");
            return;
        }

        const overlay = document.getElementById('overlay');
        const betModal = document.getElementById('bet-modal');
        const gameContainer = document.getElementById('game-container');

        const hint = document.getElementById('bet-amount');

        hint.placeholder = "Place at most " + this.currentChips + " chips";

        // Show overlay and modal
        overlay.style.display = 'block';
        betModal.style.display = 'block';

        // Disable background content
        gameContainer.style.pointerEvents = 'none';
    }

    hideBetModal() {
        const overlay = document.getElementById('overlay');
        const betModal = document.getElementById('bet-modal');
        const gameContainer = document.getElementById('game-container');

        // Hide overlay and modal
        overlay.style.display = 'none';
        betModal.style.display = 'none';
        // Enable background content
        gameContainer.style.pointerEvents = 'auto';

        this.startRound();
    }

    submitBet() {
        const betAmount = Number(document.getElementById('bet-amount').value);
        console.log("Submitted bet:", betAmount, Number.isInteger(betAmount));
        if (!Number.isInteger(betAmount) || betAmount <= 0){
            alert("Please enter a positive integer.");
        }
        else if (betAmount > this.currentChips){
            alert("You dont have that much chips.");
        }
        else {
            console.log(`Player bet: $${betAmount}`);
            // Store the bet in game data or handle it as needed
            this.placedBet = betAmount;
            this.currentChips -= betAmount;

            
            // Hide the modal after bet is entered
            this.hideBetModal();
        }
    }

    updateHistoryTable() {
        const tableBody = document.getElementById('history-table').getElementsByTagName('tbody')[0];
        tableBody.innerHTML = ''; // Clear the current table content

        // Loop through the history array and create table rows
        this.history.forEach((entry) => {
            const row = document.createElement('tr');

            // Create table cells for each result
            row.innerHTML = `
                <td>${entry.round}</td>
                <td>$${entry.bet}</td>
                <td>${entry.result}</td>
                <td>${entry.chips}</td>
            `;
            
            tableBody.appendChild(row);
        });
    }

    restartGame(){
        this.saveGameData(true);
        this.busy = false;
        this.isRoundOver = false;
        this.playerHand = [];
        this.dealerHand = [];
        this.lock();
        this.initGame();
        this.isRoundOver = true;
        console.log("reset");
    }
}

const game = new BlackjackGame();
