(function () {

    const SVG_NS = "http://www.w3.org/2000/svg";

    const BOX_WIDTH = 250;
    const BOX_HEIGHT = 60;
    const H_GAP = 120;
    const V_GAP = 30;

    function createSvg(width, height) {
        const svg = document.createElementNS(SVG_NS, "svg");
        svg.setAttribute("width", width);
        svg.setAttribute("height", height);
        svg.setAttribute("style", "background:#fafafa");
        return svg;
    }

    function isHighlighted(game) {
        if (!highlightTeam) {
            return false;
        }

        return game.teams.some(t => t.teamName === highlightTeam);
    }

    function gameContainsHighlightedTeam(game) {
        if (!highlightTeam) {
            return false;
        }

        return game.teams.some(t => t.teamName === highlightTeam);
    }

    function drawGame(svg, x, y, game) {

        const group = document.createElementNS(SVG_NS, "g");

        const rect = document.createElementNS(SVG_NS, "rect");
        rect.setAttribute("x", x);
        rect.setAttribute("y", y);
        rect.setAttribute("width", BOX_WIDTH);
        rect.setAttribute("height", BOX_HEIGHT);
        rect.setAttribute("rx", 8);
        rect.setAttribute("fill", "#ffffff");

        const highlighted = isHighlighted(game);

        if (highlighted) {
            rect.setAttribute("stroke", "#d63384");
            rect.setAttribute("stroke-width", "3");
        } else {
            rect.setAttribute("stroke", "#333");
            rect.setAttribute("stroke-width", "1");
        }

        group.appendChild(rect);

        game.teams.forEach((team, index) => {
            const text = document.createElementNS(SVG_NS, "text");

            text.setAttribute("x", x + 10);
            text.setAttribute("y", y + 20 + index * 24);

            text.textContent = team.teamName + " (" + team.score + ")";

            if (team.isWinner) {
                text.setAttribute("font-weight", "bold");
                text.setAttribute("fill", "green");
            }

            if (team.teamName === highlightTeam) {
                text.setAttribute("fill", "#d63384");
                text.setAttribute("font-weight", "bold");
            }

            group.appendChild(text);
        });

        svg.appendChild(group);
    }

    function drawLine(svg, x1, y1, x2, y2, highlighted = false) {

        const line = document.createElementNS(SVG_NS, "line");

        line.setAttribute("x1", x1);
        line.setAttribute("y1", y1);
        line.setAttribute("x2", x2);
        line.setAttribute("y2", y2);

        if (highlighted) {
            line.setAttribute("stroke", "#d63384");
            line.setAttribute("stroke-width", "3");
        } else {
            line.setAttribute("stroke", "#666");
            line.setAttribute("stroke-width", "2");
        }

        svg.appendChild(line);
    }

    function renderBracket(data) {

        const stages = data.stages;

        const maxGames = Math.max(...stages.map(s => s.games.length));

        const svgWidth = stages.length * (BOX_WIDTH + H_GAP) + 100;
        const svgHeight = maxGames * (BOX_HEIGHT + V_GAP) + 100;

        const svg = createSvg(svgWidth, svgHeight);

        const gamePositions = [];

        stages.forEach((stage, stageIndex) => {

            gamePositions[stageIndex] = [];

            stage.games.forEach((game, gameIndex) => {

                const x = stageIndex * (BOX_WIDTH + H_GAP) + 40;

                const totalGames = stage.games.length;
                const offset = (svgHeight - (totalGames * (BOX_HEIGHT + V_GAP))) / 2;

                const y = offset + gameIndex * (BOX_HEIGHT + V_GAP);

                drawGame(svg, x, y, game);

                gamePositions[stageIndex].push({
                    x: x,
                    y: y
                });

                if (stageIndex > 0) {

                    const prevStage = gamePositions[stageIndex - 1];

                    const from1 = prevStage[gameIndex * 2];
                    const from2 = prevStage[gameIndex * 2 + 1];

                    if (from1 && from2) {

                        // Sprawdzamy czy w obecnym meczu gra zaznaczona drużyna
                        const currentHasTeam = gameContainsHighlightedTeam(game);

                        // Sprawdzamy czy drużyna była w którymś z poprzednich meczów
                        const prevGame1 = stages[stageIndex - 1].games[gameIndex * 2];
                        const prevGame2 = stages[stageIndex - 1].games[gameIndex * 2 + 1];

                        const highlightLine1 = currentHasTeam && gameContainsHighlightedTeam(prevGame1);
                        const highlightLine2 = currentHasTeam && gameContainsHighlightedTeam(prevGame2);

                        drawLine(
                            svg,
                            from1.x + BOX_WIDTH,
                            from1.y + BOX_HEIGHT / 2,
                            x,
                            y + BOX_HEIGHT / 2,
                            highlightLine1
                        );

                        drawLine(
                            svg,
                            from2.x + BOX_WIDTH,
                            from2.y + BOX_HEIGHT / 2,
                            x,
                            y + BOX_HEIGHT / 2,
                            highlightLine2
                        );
                    }
                }

            });

            const title = document.createElementNS(SVG_NS, "text");
            title.setAttribute("x", stageIndex * (BOX_WIDTH + H_GAP) + 40);
            title.setAttribute("y", 30);
            title.setAttribute("font-size", "16");
            title.setAttribute("font-weight", "bold");
            title.textContent = stage.name;

            svg.appendChild(title);

        });

        document.getElementById("bracket-container").appendChild(svg);
    }

    if (typeof bracketData !== "undefined") {
        renderBracket(bracketData);
    }

})();
