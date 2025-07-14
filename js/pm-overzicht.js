const stepPrefixes = {
    "1": "cb1_", "2": "cb2_", "3": "cb3_", "4": "cb4_", "5": "cb5_",
    "6": "cb6_", "7": "cb7_", "8": "cb8_", "9": "cb9_", "10": "cb10_"
};

const phaseDefs = [
    { name: "Voorbereiding", steps: ["1", "2"] },
    { name: "Monitoring", steps: ["3", "4"] },
    { name: "Analyse & Dialoog", steps: ["5", "6"] },
    { name: "Beleidsvorming", steps: ["7", "8"] },
    { name: "Uitvoering & Evaluatie", steps: ["9", "10"] }
];

// Maak vlakke array van gemeenten
const gemeenten = Object.values(gemeentenData[0]);
const container = d3.select("#visualisatie");
const select = d3.select("#filterJaar");
const tooltip = d3.select("#tooltip");
const detailBox = d3.select("#detailContent");
const uniekeJaren = [...new Set(gemeenten.map(g => g.sinds).filter(Boolean))].sort();

uniekeJaren.forEach(jaar => {
    select.append("option").attr("value", jaar).text(jaar);
});

select.on("change", function () {
    const jaar = this.value;
    render(gemeenten.filter(g => !jaar || g.sinds === jaar));
});
const groepen = d3.groups(gemeenten, d => d.sinds || "onbekend");
groepen.sort((a, b) => {
    if (a[0] === "onbekend") return 1;
    if (b[0] === "onbekend") return -1;
    return a[0] - b[0];
});

function render(data) {
    container.html("");
    const margin = { top: 80, right: 10, bottom: 50, left: 150 };
    const cellSize = 30;
    const width = 700;
    const height = cellSize * data.length + margin.top + margin.bottom;

    const steps = Object.keys(maxScores);
    const svg = container.append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    const x = d3.scaleBand().domain(steps).range([0, width]).padding(0.05);
    const y = d3.scaleBand().domain(data.map(d => d.naam)).range([0, cellSize * data.length]).padding(0.05);

    const color = d3.scaleLinear().domain([0, 1]).range(["#ffffff", "#1f77b4"]);
    // 1. Sorteer de vlakke array voordat je hem aan D3 overdraagt
    gemeenten.sort((a, b) => {
        // lege/NULL jaren helemaal naar het einde
        if (!a.sinds && b.sinds) return 1;
        if (!b.sinds && a.sinds) return -1;

        // beide hebben een jaar óf allebei null
        if (a.sinds !== b.sinds) return (a.sinds || 9999) - (b.sinds || 9999);

        // zelfde jaar → alfabet
        return a.naam.localeCompare(b.naam, 'nl');
    });

    svg.append("g").attr("class", "axis").call(d3.axisLeft(y));
    svg.append("g").attr("class", "axis").attr("transform", `translate(0, ${cellSize * data.length})`).call(d3.axisBottom(x));

    svg.selectAll(".cell")
        .data(data.flatMap(gem => steps.map(step => {
            const score = gem.scores[step] || 0;
            const perc = score / maxScores[step];
            return {
                gemeente: gem.naam,
                invuller: gem.invuller || "-",
                datum: gem.datum || "-",
                step,
                score,
                perc,
                checks: gem.checks || []
            };
        })))
        .enter()
        .append("rect")
        .attr("x", d => x(d.step))
        .attr("y", d => y(d.gemeente))
        .attr("width", x.bandwidth())
        .attr("height", y.bandwidth())
        .attr("fill", d => color(d.perc))
        .on("mouseover", (event, d) => {
            d3.select("#tooltip")
                .style("opacity", 1)
                .html(`<strong>${decodeHtml(d.gemeente)}</strong></br>${stepNames[d.step]}<br>Score: ${d.score}/${maxScores[d.step]} (${Math.round(d.perc * 100)}%)`);
        })
        .on("mousemove", (event) => {
            d3.select("#tooltip")
                .style("left", (event.pageX + 10) + "px")
                .style("top", (event.pageY + 10) + "px");
        })
        .on("mouseout", () => {
            d3.select("#tooltip").style("opacity", 0);
        })
        .on("click", function (event, d) {
            const prefix = stepPrefixes[d.step];
            const allStepItems = Object.keys(itemlabels[0]).filter(c => c.startsWith(prefix));
            const checked = d.checks.filter(c => c.startsWith(prefix));
            const unchecked = allStepItems.filter(c => !checked.includes(c));

            const checkedList = checked.map(c => itemlabels[0][c] || c);
            const uncheckedList = unchecked.map(c => itemlabels[0][c] || c);
            document.getElementById("invullerNaam").textContent = d.invuller || "-";
            document.getElementById("invulDatum").textContent = formatDatum(d.datum) || "-";

            const content = `<h3>${d.gemeente}</h3><h2>${stepNames[d.step]}</h2>` +
                `<p><strong>${d.score} van de ${maxScores[d.step]} items</strong> aangevinkt (${Math.round(d.perc * 100)}%)</p>` +
                `<h4>Wel:</h4>` +
                (checkedList.length ? `<ul>${checkedList.map(i => `<li>${i}</li>`).join('')}</ul>` : `<p>Geen</p>`) +
                `<h4>Niet:</h4>` +
                (uncheckedList.length ? `<ul>${uncheckedList.map(i => `<li>${i}</li>`).join('')}</ul>` : `<p>Geen</p>`);

            detailBox.html(content);
        });


    phaseDefs.forEach(fase => {
        const xStart = x(fase.steps[0]);
        const xEnd = x(fase.steps[fase.steps.length - 1]) + x.bandwidth();
        if (xStart !== undefined && xEnd !== undefined) {
            svg.append("line")
                .attr("x1", xStart)
                .attr("x2", xEnd)
                .attr("y1", -30)
                .attr("y2", -30)
                .attr("class", "phase-line");

            svg.append("text")
                .attr("x", (xStart + xEnd) / 2)
                .attr("y", -35)
                .attr("class", "phase-label")
                .text(decodeHtml(fase.name));
        }
    });
}
function decodeHtml(html) {
    const txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
}
function formatDatum(input) {
    const d = new Date(input);
    return d.toLocaleString('nl-NL', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).replace(' om', ' om'); // optioneel: 'om' ertussen
}

render(gemeenten);