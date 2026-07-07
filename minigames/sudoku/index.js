const gridElement = document.getElementById('sudoku-grid');
const messageElement = document.getElementById('message');
let solution = [];
function isValid(board, row, col, num) {
    for (let x = 0; x < 9; x++) {
        if (board[row][x] === num || board[x][col] === num) return false;
    }
    let startRow = row - row % 3, startCol = col - col % 3;
    for (let i = 0; i < 3; i++) {
        for (let j = 0; j < 3; j++) {
            if (board[i + startRow][j + startCol] === num) return false;
        }
    }
    return true;
}
function solveSudoku(board) {
    for (let row = 0; row < 9; row++) {
        for (let col = 0; col < 9; col++) {
            if (board[row][col] === 0) {
                let nums = [1,2,3,4,5,6,7,8,9].sort(() => Math.random() - 0.5);
                for (let num of nums) {
                    if (isValid(board, row, col, num)) {
                        board[row][col] = num;
                        if (solveSudoku(board)) return true;
                        board[row][col] = 0;
                    }
                }
                return false;
            }
        }
    }
    return true;
}
function generateBoard() {
    let board = Array.from({ length: 9 }, () => Array(9).fill(0));
    solveSudoku(board);
    solution = board.map(row => [...row]);
    
    let playable = board.map(row => [...row]);
    let attempts = 40;
    while (attempts > 0) {
        let r = Math.floor(Math.random() * 9);
        let c = Math.floor(Math.random() * 9);
        if (playable[r][c] !== 0) {
            playable[r][c] = 0;
            attempts--;
        }
    }
    return playable;
}
function initGame() {
    gridElement.innerHTML = '';
    messageElement.innerText = '';
    const board = generateBoard();
    for (let r = 0; r < 9; r++) {
        for (let c = 0; c < 9; c++) {
            const cell = document.createElement('div');
            cell.className = 'cell';
            
            if (board[r][c] !== 0) {
                cell.innerText = board[r][c];
                cell.style.color = 'var(--fixed-color)';
            } else {
                const input = document.createElement('input');
                input.type = 'text';
                input.maxLength = 1;
                input.oninput = (e) => {
                    e.target.value = e.target.value.replace(/[^1-9]/g, '');
                    checkBoard();
                };
                cell.appendChild(input);
            }
            gridElement.appendChild(cell);
        }
    }
}
function checkBoard() {
    const inputs = document.querySelectorAll('.cell');
    let complete = true;
    let win = true;
    inputs.forEach((cell, index) => {
        const r = Math.floor(index / 9);
        const c = index % 9;
        const val = cell.innerText || cell.firstChild.value;
        if (!val) {
            complete = false;
        } else if (parseInt(val) !== solution[r][c]) {
            win = false;
            if (cell.firstChild.tagName === 'INPUT') {
                cell.classList.add('invalid');
            }
        } else {
            cell.classList.remove('invalid');
        }
    });
    if (complete && win) {
        messageElement.innerText = "You solved it!";
        messageElement.style.color = "green";
    } else {
        messageElement.innerText = "";
    }
}
initGame();