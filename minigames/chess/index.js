document.addEventListener("DOMContentLoaded", () => {
  const game = new Chess();
  let selectedSquare = null;
  let gameActive = true;
  let playerColor = 'w';
  
  let playerElo = parseInt(localStorage.getItem('chess_elo')) || 300;
  const botElos = {
    '1': 300,
    '2': 1100,
    '3': 1300
  };
  const playerCard = new window.ECMediaCard({
    author: "You (White)",
    content: `<b>Elo Rating:</b> ${playerElo}`,
    avatarSrc: "https://api.dicebear.com/7.x/avataaars/svg?seed=Player"
  });
  document.getElementById('player-info-container').appendChild(playerCard.element);
  
  const botCard = new window.ECMediaCard({
    author: "ChessBot (Black)",
    content: `<b>Elo Rating:</b> 1100`,
    avatarSrc: "https://api.dicebear.com/7.x/bottts/svg?seed=ChessBot"
  });
  document.getElementById('bot-info-container').appendChild(botCard.element);
  
  const colorDropdown = new window.ECDropdown({
    label: "Play as",
    items: [
      { label: "Random Color", value: "random" },
      { label: "White", value: "w" },
      { label: "Black", value: "b" }
    ]
  });
  document.getElementById('color-dropdown-container').appendChild(colorDropdown.element);
  const botLevelDropdown = new window.ECDropdown({
    label: "AI Difficulty Level",
    items: [
      { label: "L1 (300 Elo)", value: "1" },
      { label: "L2 (1100 Elo)", value: "2" },
      { label: "L3 (1300 Elo)", value: "3" }
    ]
  });
  botLevelDropdown.setValue("2");
  botLevelDropdown.onChange((val) => {
    botCard.setContent(`<b>Elo Rating:</b> ${botElos[val]}`);
  });
  document.getElementById('dropdown-container').appendChild(botLevelDropdown.element);
  const logFormatDropdown = new window.ECDropdown({
    items: [
      { label: "Move List", value: "list" },
      { label: "PGN (Raw)", value: "pgn" }
    ]
  });
  logFormatDropdown.onChange(() => updateSidebar());
  document.getElementById('log-format-container').appendChild(logFormatDropdown.element);
  const newGameBtn = new window.ECButton("Start New Game", { variant: "filled" });
  newGameBtn.onClick(() => startGame());
  document.getElementById('new-game-btn-container').appendChild(newGameBtn.element);
  const pgnBox = document.getElementById('pgn-container');
  const moveListBox = document.getElementById('movelist-container');
  const boardContainer = document.getElementById('board-container');
  
  const solidPieces = { 'p': '♟', 'n': '♞', 'b': '♝', 'r': '♜', 'q': '♛', 'k': '♚' };
  function startGame() {
    game.reset();
    selectedSquare = null;
    gameActive = true;
    
    const colorPref = colorDropdown.getValue();
    playerColor = colorPref === 'random' ? (Math.random() > 0.5 ? 'w' : 'b') : colorPref;
    playerCard.element.querySelector('p').textContent = `You (${playerColor === 'w' ? 'White' : 'Black'})`;
    botCard.element.querySelector('p').textContent = `ChessBot (${playerColor === 'w' ? 'Black' : 'White'})`;
    updateBoard();
    updateSidebar();
    if (playerColor === 'b') {
      makeBotMove();
    }
  }
  function updateSidebar() {
    const format = logFormatDropdown.getValue();
    
    if (format === 'pgn') {
      pgnBox.classList.replace("display-none", "display-block");
      moveListBox.classList.replace("display-flex", "display-none");
      pgnBox.value = game.pgn() || "Moves will appear here...";
      pgnBox.scrollTop = pgnBox.scrollHeight;
    } else {
      pgnBox.classList.replace("display-block", "display-none");
      moveListBox.classList.replace("display-none", "display-flex");
      
      moveListBox.innerHTML = '';
      const history = game.history();
      
      if (history.length === 0) {
        moveListBox.innerHTML = '<div class="padding-12px color-var(--ec-text-muted,_#6c757d) fontSize-14px">Moves will appear here...</div>';
      } else {
        for (let i = 0; i < history.length; i += 2) {
          const row = document.createElement("div");
          row.className = "display-flex alignItems-center padding-8px_12px fontSize-14px borderBottom-1px_solid_var(--ec-border,_#dee2e6)";
          if ((i / 2) % 2 === 1) row.classList.add("background-var(--ec-bg,_#fff)");
          
          const whiteMove = history[i];
          const blackMove = history[i+1] || '';
          
          row.innerHTML = `
            <div class="color-var(--ec-text-muted,_#6c757d) width-36px fontWeight-600">${i / 2 + 1}.</div>
            <div class="flex-1 color-var(--ec-text,_#212529)">${whiteMove}</div>
            <div class="flex-1 color-var(--ec-text,_#212529)">${blackMove}</div>
          `;
          moveListBox.appendChild(row);
        }
      }
      moveListBox.scrollTop = moveListBox.scrollHeight;
    }
  }
  function updateElo(score) {
    const level = botLevelDropdown.getValue();
    const botElo = botElos[level] || 300;
    
    let K = 32;
    if (playerElo < 1200) K = 40;
    else if (playerElo >= 2000) K = 16;
    else if (playerElo >= 2400) K = 10;
    
    const expected = 1 / (1 + Math.pow(10, (botElo - playerElo) / 400));
    const eloChange = Math.round(K * (score - expected));
    let newElo = playerElo + eloChange;
    
    if (newElo < 100) newElo = 100;
    
    const actualChange = newElo - playerElo;
    playerElo = newElo;
    
    localStorage.setItem('chess_elo', playerElo);
    playerCard.setContent(`<b>Elo Rating:</b> ${playerElo}`);
    
    const changeStr = actualChange > 0 ? `+${actualChange}` : `${actualChange}`;
    
    let resultText = 'Draw';
    let toastType = 'info';
    
    if (score === 1) {
      resultText = 'Victory!';
      toastType = 'success';
    } else if (score === 0) {
      resultText = 'Defeat...';
      toastType = 'error';
    }

    const toastMessage = `${resultText} New Elo: ${playerElo} (${changeStr})`;
    const toast = new window.ECToast(toastMessage, { type: toastType, duration: 4000 });
    toast.show();
  }
  function checkGameOver() {
    if (game.game_over() && gameActive) {
      gameActive = false;
      if (game.in_checkmate()) {
        const playerWon = game.turn() !== playerColor;
        const winnerName = playerWon ? 'Player' : 'Bot';
        
        const toast = new window.ECToast(`Checkmate! ${winnerName} wins.`, { type: playerWon ? 'success' : 'error' });
        toast.show();
        
        updateElo(playerWon ? 1 : 0);
      } else {
        const toast = new window.ECToast(`Game drawn!`, { type: 'warning' });
        toast.show();
        updateElo(0.5);
      }
    }
  }
  function handleSquareClick(sq) {
    if (!gameActive || game.turn() !== playerColor) return;
    if (!selectedSquare) {
      const piece = game.get(sq);
      if (piece && piece.color === playerColor) {
        selectedSquare = sq;
        updateBoard();
      }
    } else {
      const moves = game.moves({ verbose: true });
      let validMoves = moves.filter(m => m.from === selectedSquare && m.to === sq);
      if (validMoves.length > 0) {
        let moveObj = validMoves[0];
        if (moveObj.promotion) {
          moveObj = validMoves.find(m => m.promotion === 'q') || validMoves[0];
        }
        
        game.move(moveObj.san);
        selectedSquare = null;
        updateBoard();
        updateSidebar();
        checkGameOver();
        
        if (gameActive && game.turn() !== playerColor) {
          makeBotMove();
        }
      } else {
        const piece = game.get(sq);
        if (piece && piece.color === playerColor) {
          selectedSquare = sq;
          updateBoard();
        } else {
          selectedSquare = null;
          updateBoard();
        }
      }
    }
  }
  function updateBoard() {
    boardContainer.innerHTML = '';
    const board = game.board();
    const moves = game.moves({ verbose: true });
    const validDestinations = selectedSquare ? moves.filter(m => m.from === selectedSquare).map(m => m.to) : [];
    const isWhitePerspective = playerColor === 'w';
    for (let visualRow = 0; visualRow < 8; visualRow++) {
      for (let visualCol = 0; visualCol < 8; visualCol++) {
        
        const boardRow = isWhitePerspective ? visualRow : 7 - visualRow;
        const boardCol = isWhitePerspective ? visualCol : 7 - visualCol;
        
        const fileChar = String.fromCharCode(97 + boardCol);
        const rankNum = 8 - boardRow;
        const squareId = fileChar + rankNum;
        const squareDiv = document.createElement('div');
        
        const isDark = (visualRow + visualCol) % 2 === 1;
        const baseColor = isDark ? "background-#b58863" : "background-#f0d9b5";
        const bgClass = selectedSquare === squareId ? "background-#baca44" : baseColor;
        squareDiv.className = `display-flex alignItems-center justifyContent-center fontSize-36px cursor-pointer userSelect-none transition-background_0.15s_ease position-relative ${bgClass}`;
        
        const piece = board[boardRow][boardCol];
        if (piece) {
          squareDiv.textContent = solidPieces[piece.type];
          if(piece.color === 'w') {
             squareDiv.style.color = '#ffffff';
             squareDiv.style.textShadow = '0 2px 4px rgba(0,0,0,0.6)';
          } else {
             squareDiv.style.color = '#212529';
             squareDiv.style.textShadow = '0 1px 2px rgba(255,255,255,0.3)';
          }
        }
        if (visualCol === 0) {
          const rankLabel = document.createElement('span');
          rankLabel.textContent = rankNum;
          rankLabel.className = "position-absolute top-4px left-4px fontSize-11px fontWeight-700 pointerEvents-none";
          rankLabel.style.color = isDark ? "#f0d9b5" : "#b58863";
          squareDiv.appendChild(rankLabel);
        }
        if (visualRow === 7) {
          const fileLabel = document.createElement('span');
          fileLabel.textContent = fileChar;
          fileLabel.className = "position-absolute bottom-4px right-4px fontSize-11px fontWeight-700 pointerEvents-none";
          fileLabel.style.color = isDark ? "#f0d9b5" : "#b58863";
          squareDiv.appendChild(fileLabel);
        }
        if (validDestinations.includes(squareId)) {
          const dot = document.createElement('div');
          dot.className = "width-16px height-16px borderRadius-50% background-rgba(0,0,0,0.2) position-absolute top-50% left-50% transform-translate(-50%,_-50%) pointerEvents-none";
          squareDiv.appendChild(dot);
        }
        squareDiv.addEventListener('click', () => handleSquareClick(squareId));
        boardContainer.appendChild(squareDiv);
      }
    }
  }

  const PIECE_VALUES = { p: 100, n: 320, b: 330, r: 500, q: 900, k: 20000 };

  const PST = {
    p: [[0,  0,  0,  0,  0,  0,  0,  0],[50, 50, 50, 50, 50, 50, 50, 50],[10, 10, 20, 30, 30, 20, 10, 10],[5,  5, 10, 25, 25, 10,  5,  5],[0,  0,  0, 20, 20,  0,  0,  0],[5, -5,-10,  0,  0,-10, -5,  5],[5, 10, 10,-20,-20, 10, 10,  5],[0,  0,  0,  0,  0,  0,  0,  0]
    ],
    n: [[-50,-40,-30,-30,-30,-30,-40,-50],[-40,-20,  0,  0,  0,  0,-20,-40],[-30,  0, 10, 15, 15, 10,  0,-30],[-30,  5, 15, 20, 20, 15,  5,-30],[-30,  0, 15, 20, 20, 15,  0,-30],[-30,  5, 10, 15, 15, 10,  5,-30],[-40,-20,  0,  5,  5,  0,-20,-40],[-50,-40,-30,-30,-30,-30,-40,-50]
    ],
    b: [[-20,-10,-10,-10,-10,-10,-10,-20],[-10,  0,  0,  0,  0,  0,  0,-10],[-10,  0,  5, 10, 10,  5,  0,-10],[-10,  5,  5, 10, 10,  5,  5,-10],[-10,  0, 10, 10, 10, 10,  0,-10],[-10, 10, 10, 10, 10, 10, 10,-10],[-10,  5,  0,  0,  0,  0,  5,-10],[-20,-10,-10,-10,-10,-10,-10,-20]
    ],
    r: [[0,  0,  0,  0,  0,  0,  0,  0],[5, 10, 10, 10, 10, 10, 10,  5],[-5,  0,  0,  0,  0,  0,  0, -5],[-5,  0,  0,  0,  0,  0,  0, -5],[-5,  0,  0,  0,  0,  0,  0, -5],[-5,  0,  0,  0,  0,  0,  0, -5],[-5,  0,  0,  0,  0,  0,  0, -5],[0,  0,  0,  5,  5,  0,  0,  0]
    ],
    q: [[-20,-10,-10, -5, -5,-10,-10,-20],[-10,  0,  0,  0,  0,  0,  0,-10],[-10,  0,  5,  5,  5,  5,  0,-10],[ -5,  0,  5,  5,  5,  5,  0, -5],[  0,  0,  5,  5,  5,  5,  0, -5],[-10,  5,  5,  5,  5,  5,  0,-10],[-10,  0,  5,  0,  0,  0,  0,-10],[-20,-10,-10, -5, -5,-10,-10,-20]
    ],
    k: [[-30,-40,-40,-50,-50,-40,-40,-30],[-30,-40,-40,-50,-50,-40,-40,-30],[-30,-40,-40,-50,-50,-40,-40,-30],[-30,-40,-40,-50,-50,-40,-40,-30],[-20,-30,-30,-40,-40,-30,-30,-20],[-10,-20,-20,-20,-20,-20,-20,-10],[ 20, 20,  0,  0,  0,  0, 20, 20],[ 20, 30, 10,  0,  0, 10, 30, 20]
    ]
  };

  function evaluateBoard(gameNode) {
    let score = 0;
    const board = gameNode.board();

    for (let r = 0; r < 8; r++) {
      for (let c = 0; c < 8; c++) {
        const piece = board[r][c];
        if (piece) {
          const pstRow = piece.color === 'w' ? r : 7 - r;
          const posBonus = PST[piece.type][pstRow][c];

          const val = PIECE_VALUES[piece.type] + posBonus;
          score += piece.color === 'w' ? val : -val;
        }
      }
    }
    return score;
  }

  function orderMoves(moves) {
    return moves.sort((a, b) => {
      let scoreA = 0;
      let scoreB = 0;

      if (a.captured) scoreA += 10 * PIECE_VALUES[a.captured] - PIECE_VALUES[a.piece];
      if (a.flags.includes('p')) scoreA += PIECE_VALUES[a.promotion] || 900;

      if (b.captured) scoreB += 10 * PIECE_VALUES[b.captured] - PIECE_VALUES[b.piece];
      if (b.flags.includes('p')) scoreB += PIECE_VALUES[b.promotion] || 900;

      return scoreB - scoreA;
    });
  }

  function quiescence(gameNode, alpha, beta, isMaximizing, qsDepth = 0) {
    const standPat = evaluateBoard(gameNode);

    if (isMaximizing) {
      if (standPat >= beta) return beta;
      if (alpha < standPat) alpha = standPat;
    } else {
      if (standPat <= alpha) return alpha;
      if (standPat < beta) beta = standPat;
    }

    if (qsDepth > 4) return standPat;

    const captures = gameNode.moves({ verbose: true }).filter(m => m.captured);
    const orderedCaptures = orderMoves(captures);

    for (const move of orderedCaptures) {
      gameNode.move(move.san);
      const score = quiescence(gameNode, alpha, beta, !isMaximizing, qsDepth + 1);
      gameNode.undo();

      if (isMaximizing) {
        if (score >= beta) return beta;
        if (score > alpha) alpha = score;
      } else {
        if (score <= alpha) return alpha;
        if (score < beta) beta = score;
      }
    }

    return isMaximizing ? alpha : beta;
  }

  function minimax(gameNode, depth, alpha, beta, isMaximizing) {
    const isGameOver = typeof gameNode.isGameOver === 'function' ? gameNode.isGameOver() : gameNode.game_over();

    if (isGameOver) {
      if (gameNode.in_draw() || gameNode.in_stalemate() || gameNode.in_threefold_repetition()) return 0;
      
      return gameNode.turn() === 'w' ? -99999 - depth : 99999 + depth;
    }

    if (depth === 0) {
      return quiescence(gameNode, alpha, beta, isMaximizing, 0);
    }

    const moves = orderMoves(gameNode.moves({ verbose: true }));

    if (isMaximizing) {
      let maxEval = -Infinity;
      for (const move of moves) {
        gameNode.move(move.san);
        const evalScore = minimax(gameNode, depth - 1, alpha, beta, false);
        gameNode.undo();
        
        maxEval = Math.max(maxEval, evalScore);
        alpha = Math.max(alpha, evalScore);
        if (beta <= alpha) break;
      }
      return maxEval;
    } else {
      let minEval = Infinity;
      for (const move of moves) {
        gameNode.move(move.san);
        const evalScore = minimax(gameNode, depth - 1, alpha, beta, true);
        gameNode.undo();
        
        minEval = Math.min(minEval, evalScore);
        beta = Math.min(beta, evalScore);
        if (beta <= alpha) break;
      }
      return minEval;
    }
  }

  function getBestMove(gameNode, depth) {
    const moves = gameNode.moves({ verbose: true });
    if (moves.length === 0) return null;
    
    const orderedMoves = orderMoves(moves);
    const isWhite = gameNode.turn() === 'w';

    let bestMove = null;
    let bestValue = isWhite ? -Infinity : Infinity;

    for (const move of orderedMoves) {
      gameNode.move(move.san);
      const boardValue = minimax(gameNode, depth - 1, -Infinity, Infinity, !isWhite);
      gameNode.undo();

      if (isWhite) {
        if (boardValue > bestValue) {
          bestValue = boardValue;
          bestMove = move;
        }
      } else {
        if (boardValue < bestValue) {
          bestValue = boardValue;
          bestMove = move;
        }
      }
    }

    return bestMove ? bestMove.san : orderedMoves[0].san;
  }

  function makeBotMove() {
    if (!gameActive) return;
    const level = botLevelDropdown.getValue();
    const mvs = game.moves();
    let move;
    
    if (level === '1') { 
      move = mvs[Math.floor(Math.random() * mvs.length)];
    } else if (level === '2') { 
      move = getBestMove(game, 1);
    } else if (level === '3') { 
      move = getBestMove(game, 2);
    }
    
    game.move(move);
    updateBoard();
    updateSidebar();
    checkGameOver();
  }
  startGame();
});