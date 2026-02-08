/**
 * @typedef {Object} TeamResultDto
 * @property {string} teamName
 * @property {number} score
 * @property {boolean} isWinner
 */

/**
 * @typedef {Object} GameDto
 * @property {number} id
 * @property {TeamResultDto[]} teams
 */

/**
 * @typedef {Object} StageDto
 * @property {string} name
 * @property {GameDto[]} games
 */

/**
 * @typedef {Object} BracketDto
 * @property {StageDto[]} stages
 */
(function () {
    // SVG Constants
    const SVG_NS = "http://www.w3.org/2000/svg";
    const BOX_WIDTH = 250;
    const BOX_HEIGHT = 60;
    const H_GAP = 120;
    const V_GAP = 30;
    const PADDING = 40;
    const TITLE_Y_OFFSET = 30;

    // Style Constants
    const BACKGROUND_COLOR = "#fafafa";
    const BOX_FILL = "#ffffff";
    const HIGHLIGHT_COLOR = "#d63384";
    const WINNER_COLOR = "green";
    const OTHER_COLOR = "black";
    const DEFAULT_STROKE = "#333";
    const LINE_STROKE = "#666";
    const FONT_SIZE = "14";
    const FONT_SELECTED = "bold";

    function createSvg(width, height) {
        const svg = document.createElementNS(SVG_NS, "svg");
        svg.setAttribute("width", width);
        svg.setAttribute("height", height);
        svg.setAttribute("style", `background:${BACKGROUND_COLOR}`);
        return svg;
    }

    function createSvgElement(tagName, attributes) {
        const element = document.createElementNS(SVG_NS, tagName);
        Object.entries(attributes).forEach(([key, value]) => {
            element.setAttribute(key, value);
        });
        return element;
    }

    function containsHighlightedTeam(gameDto) {
        if (!highlightTeam) {
            return false;
        }

        return gameDto.teams.some(t => t.teamName === highlightTeam);
    }

    function createGameBox(x, y, isHighlighted) {
        const attributes = {
            x, y,
            width: BOX_WIDTH,
            height: BOX_HEIGHT,
            rx: 8,
            fill: BOX_FILL,
            stroke: isHighlighted ? HIGHLIGHT_COLOR : DEFAULT_STROKE,
            "stroke-width": isHighlighted ? "3" : "1"
        };

        return createSvgElement("rect", attributes);
    }

    function createTeamText(x, y, teamResultDto, isHighlighted) {
        const text = createSvgElement("text", {
            x: x + 10,
            y,
            fill: teamResultDto.isWinner === true ? WINNER_COLOR : OTHER_COLOR
        });
        text.textContent = `${teamResultDto.teamName} (${teamResultDto.score})`;

        if (teamResultDto.isWinner === true || isHighlighted === true) {
            text.setAttribute("font-weight", FONT_SELECTED);
        }
        if (isHighlighted === true) {
            text.setAttribute("fill", HIGHLIGHT_COLOR);
        }

        return text;
    }

    function drawGame(svg, x, y, gameDto) {
        const group = document.createElementNS(SVG_NS, "g");
        const highlighted = containsHighlightedTeam(gameDto);

        group.appendChild(createGameBox(x, y, highlighted));

        gameDto.teams.forEach((teamResultDto, index) => {
            const isTeamHighlighted = teamResultDto.teamName === highlightTeam;
            const textY = y + 20 + index * 24;
            group.appendChild(createTeamText(x, textY, teamResultDto, isTeamHighlighted));
        });

        svg.appendChild(group);
    }

    function drawLine(svg, x1, y1, x2, y2, highlighted = false) {
        const attributes = {
            x1, y1, x2, y2,
            stroke: highlighted ? HIGHLIGHT_COLOR : LINE_STROKE,
            "stroke-width": highlighted ? "3" : "2"
        };
        svg.appendChild(createSvgElement("line", attributes));
    }

    function calculateGamePosition(stageIndex, gameIndex, totalGames, svgHeight) {
        const x = stageIndex * (BOX_WIDTH + H_GAP) + PADDING;
        const offset = (svgHeight - (totalGames * (BOX_HEIGHT + V_GAP))) / 2;
        const y = offset + gameIndex * (BOX_HEIGHT + V_GAP);

        return { x, y };
    }

    function shouldHighlightLine(currentGame, previousGame) {
        return containsHighlightedTeam(currentGame) && containsHighlightedTeam(previousGame);
    }

    function drawConnectionLines(svg, stages, stageIndex, gameIndex, gamePositions, currentPosition) {
        if (stageIndex === 0) return;

        const prevStage = gamePositions[stageIndex - 1];
        const from1 = prevStage[gameIndex * 2];
        const from2 = prevStage[gameIndex * 2 + 1];

        if (!from1 || !from2) return;

        const currentGame = stages[stageIndex].games[gameIndex];
        const prevGame1 = stages[stageIndex - 1].games[gameIndex * 2];
        const prevGame2 = stages[stageIndex - 1].games[gameIndex * 2 + 1];

        const lineY = currentPosition.y + BOX_HEIGHT / 2;

        drawLine(
            svg,
            from1.x + BOX_WIDTH,
            from1.y + BOX_HEIGHT / 2,
            currentPosition.x,
            lineY,
            shouldHighlightLine(currentGame, prevGame1)
        );

        drawLine(
            svg,
            from2.x + BOX_WIDTH,
            from2.y + BOX_HEIGHT / 2,
            currentPosition.x,
            lineY,
            shouldHighlightLine(currentGame, prevGame2)
        );
    }

    function drawStageTitle(svg, stageName, stageIndex) {
        const title = createSvgElement("text", {
            x: stageIndex * (BOX_WIDTH + H_GAP) + PADDING,
            y: TITLE_Y_OFFSET,
            "font-size": FONT_SIZE,
            "font-weight": FONT_SELECTED
        });
        title.textContent = stageName;
        svg.appendChild(title);
    }

    function renderBracket(bracketDto) {
        const stages = bracketDto.stages;
        const maxGames = Math.max(...stages.map(s => s.games.length));
        const svgWidth = stages.length * (BOX_WIDTH + H_GAP) + 100;
        const svgHeight = maxGames * (BOX_HEIGHT + V_GAP) + 100;
        const svg = createSvg(svgWidth, svgHeight);
        const gamePositions = [];

        stages.forEach((stageDto, stageIndex) => {
            gamePositions[stageIndex] = [];

            stageDto.games.forEach((gameDto, gameIndex) => {
                const position = calculateGamePosition(
                    stageIndex,
                    gameIndex,
                    stageDto.games.length,
                    svgHeight
                );

                drawGame(svg, position.x, position.y, gameDto);
                gamePositions[stageIndex].push(position);
                drawConnectionLines(svg, stages, stageIndex, gameIndex, gamePositions, position);
            });

            drawStageTitle(svg, stageDto.name, stageIndex);
        });

        document.getElementById("bracket-container").appendChild(svg);
    }

    if (typeof bracketDto !== "undefined") {
        renderBracket(bracketDto);
    }
})();
