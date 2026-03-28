interface TeamResultDto {
    teamName: string;
    score: number;
    isWinner: boolean;
}

interface GameDto {
    id: number;
    teams: TeamResultDto[];
    date: string
}

interface StageDto {
    name: string;
    games: GameDto[];
}

interface BracketDto {
    stages: StageDto[];
}

declare const bracketDto: BracketDto | undefined;
declare const highlightTeam: string | undefined;

// SVG Constants
const SVG_NS = "http://www.w3.org/2000/svg";
const BOX_WIDTH = 250;
const BOX_HEIGHT = 80;
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

function createSvg(width: number, height: number): SVGSVGElement {
    const svg = document.createElementNS(SVG_NS, "svg") as SVGSVGElement;
    svg.setAttribute("width", String(width));
    svg.setAttribute("height", String(height));
    svg.setAttribute("style", `background:${BACKGROUND_COLOR}`);
    return svg;
}

function createSvgElement<K extends keyof SVGElementTagNameMap>(
    tagName: K,
    attributes: Record<string, string | number>
): SVGElementTagNameMap[K] {
    const element = document.createElementNS(SVG_NS, tagName);

    Object.entries(attributes).forEach(([key, value]) => {
        element.setAttribute(key, String(value));
    });

    return element;
}

function containsHighlightedTeam(gameDto: GameDto): boolean {
    if (!highlightTeam) {
        return false;
    }

    return gameDto.teams.some(t => t.teamName === highlightTeam);
}

function createGameBox(x: number, y: number, isHighlighted: boolean): SVGRectElement {
    return createSvgElement("rect", {
        x,
        y,
        width: BOX_WIDTH,
        height: BOX_HEIGHT,
        rx: 8,
        fill: BOX_FILL,
        stroke: isHighlighted ? HIGHLIGHT_COLOR : DEFAULT_STROKE,
        "stroke-width": isHighlighted ? "3" : "1"
    });
}

function createTeamText(
    x: number,
    y: number,
    teamResultDto: TeamResultDto,
    isHighlighted: boolean
): SVGTextElement {
    const text = createSvgElement("text", {
        x: x + 10,
        y,
        fill: teamResultDto.isWinner ? WINNER_COLOR : OTHER_COLOR
    });

    text.textContent = `${teamResultDto.teamName} (${teamResultDto.score})`;

    if (teamResultDto.isWinner || isHighlighted) {
        text.setAttribute("font-weight", FONT_SELECTED);
    }

    if (isHighlighted) {
        text.setAttribute("fill", HIGHLIGHT_COLOR);
    }

    return text;
}

function drawGame(svg: SVGSVGElement, x: number, y: number, gameDto: GameDto): void {
    const group = document.createElementNS(SVG_NS, "g");
    const highlighted = containsHighlightedTeam(gameDto);

    group.appendChild(createGameBox(x, y, highlighted));

    group.appendChild(
        createGameDateText(x, y, gameDto.date)
    );

    gameDto.teams.forEach((teamResultDto, index) => {
        const isTeamHighlighted = teamResultDto.teamName === highlightTeam;
        const textY = y + 39 + index * 24;

        group.appendChild(
            createTeamText(x, textY, teamResultDto, isTeamHighlighted)
        );
    });

    svg.appendChild(group);
}

function drawLine(
    svg: SVGSVGElement,
    x1: number,
    y1: number,
    x2: number,
    y2: number,
    highlighted = false
): void {
    svg.appendChild(
        createSvgElement("line", {
            x1,
            y1,
            x2,
            y2,
            stroke: highlighted ? HIGHLIGHT_COLOR : LINE_STROKE,
            "stroke-width": highlighted ? "3" : "2"
        })
    );
}

function calculateGamePosition(
    stageIndex: number,
    gameIndex: number,
    totalGames: number,
    svgHeight: number,
    stageDto: StageDto
): { x: number; y: number } {
    const x = stageIndex * (BOX_WIDTH + H_GAP) + PADDING;

    let offset = (svgHeight - totalGames * (BOX_HEIGHT + V_GAP)) / 2;

    // zmniejsz odstęp jeśli poprzednia gra to te same drużyny i ta sama data
    if (gameIndex > 0 && areSameTeams(stageDto.games[gameIndex], stageDto.games[gameIndex - 1])) {
        offset -= V_GAP + 2; // zmniejszenie odstępu między nimi
    }

    const y = offset + gameIndex * (BOX_HEIGHT + V_GAP);

    return { x, y };
}

function shouldHighlightLine(currentGame: GameDto, previousGame: GameDto): boolean {
    return containsHighlightedTeam(currentGame) && containsHighlightedTeam(previousGame);
}

function teamsOverlap(gameA: GameDto, gameB: GameDto): boolean {
    const teamsA = new Set(gameA.teams.map(t => t.teamName));
    return gameB.teams.some(t => teamsA.has(t.teamName));
}

function drawConnectionLines(
    svg: SVGSVGElement,
    stages: StageDto[],
    stageIndex: number,
    gameIndex: number,
    gamePositions: Array<Array<{ x: number; y: number }>>,
    currentPosition: { x: number; y: number }
): void {
    if (stageIndex === 0) return;

    const prevStageGames = stages[stageIndex - 1].games;
    const prevPositions = gamePositions[stageIndex - 1];
    const currentGame = stages[stageIndex].games[gameIndex];
    const currentMidY = currentPosition.y + BOX_HEIGHT / 2;
    const processed = new Set<number>();

    prevStageGames.forEach((prevGame, i) => {
        if (processed.has(i)) return;

        // ← kluczowa zmiana: tylko gry dzielące drużynę z bieżącą
        if (!teamsOverlap(prevGame, currentGame)) return;

        // znajdź grupę "sklejonych" meczów (te same drużyny + ta sama data)
        const group = prevStageGames
            .map((g, idx) => ({ game: g, pos: prevPositions[idx], idx }))
            .filter(({ game }) => areSameTeams(game, prevGame));

        group.forEach(({ idx }) => processed.add(idx));

        const highlighted =
            group.some(p => containsHighlightedTeam(p.game)) &&
            containsHighlightedTeam(currentGame);

        if (group.length > 1) {
            // jedna linia ze środka grupy sklejonych ramek
            const minY = Math.min(...group.map(p => p.pos.y));
            const maxY = Math.max(...group.map(p => p.pos.y));
            const midY = minY + (maxY - minY + BOX_HEIGHT) / 2;

            drawLine(svg, group[0].pos.x + BOX_WIDTH, midY, currentPosition.x, currentMidY, highlighted);
        } else {
            drawLine(
                svg,
                prevPositions[i].x + BOX_WIDTH,
                prevPositions[i].y + BOX_HEIGHT / 2,
                currentPosition.x,
                currentMidY,
                shouldHighlightLine(currentGame, prevGame)
            );
        }
    });
}

function drawStageTitle(svg: SVGSVGElement, stageName: string, stageIndex: number): void {
    const title = createSvgElement("text", {
        x: stageIndex * (BOX_WIDTH + H_GAP) + PADDING,
        y: TITLE_Y_OFFSET,
        "font-size": FONT_SIZE,
        "font-weight": FONT_SELECTED
    });

    title.textContent = stageName;
    svg.appendChild(title);
}

function renderBracket(bracketDto: BracketDto): void {
    const stages = bracketDto.stages;

    const maxGames = Math.max(...stages.map(s => s.games.length));

    const svgWidth = stages.length * (BOX_WIDTH + H_GAP) + 100;
    const svgHeight = maxGames * (BOX_HEIGHT + V_GAP) + 100;

    const svg = createSvg(svgWidth, svgHeight);
    const gamePositions: Array<Array<{ x: number; y: number }>> = [];

    stages.forEach((stageDto, stageIndex) => {
        gamePositions[stageIndex] = [];

        stageDto.games.forEach((gameDto, gameIndex) => {
            const position = calculateGamePosition(
                stageIndex,
                gameIndex,
                stageDto.games.length,
                svgHeight,
                stageDto
            );

            drawGame(svg, position.x, position.y, gameDto);
            gamePositions[stageIndex].push(position);

            drawConnectionLines(
                svg,
                stages,
                stageIndex,
                gameIndex,
                gamePositions,
                position
            );
        });

        drawStageTitle(svg, stageDto.name, stageIndex);
    });

    const container = document.getElementById("bracket-container");
    if (container) {
        container.appendChild(svg);
    }
}

function createGameDateText(
    x: number,
    y: number,
    dateString: string
): SVGTextElement {
    const text = createSvgElement("text", {
        x: x + 10,
        y: y + 15,
        fill: "#000",
        "font-size": "12",
        "font-weight": "bold"
    });

    text.textContent = dateString;

    return text;
}

function areSameTeams(gameA: GameDto, gameB: GameDto): boolean {
    if (!gameA || !gameB) return false;

    const teamsA = gameA.teams.map(t => t.teamName).sort().join(',');
    const teamsB = gameB.teams.map(t => t.teamName).sort().join(',');

    return teamsA === teamsB && gameA.date === gameB.date;
}

if (typeof bracketDto !== "undefined") {
    renderBracket(bracketDto);
}
