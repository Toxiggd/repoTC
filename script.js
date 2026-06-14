let authToken = null;

///logowanie
document.getElementById("loginBtn").addEventListener("click", () => {
    const username = document.getElementById("loginUsername").value.trim();
    const password = document.getElementById("loginPassword").value.trim();

    if (!username || !password) {
        document.getElementById("loginError").textContent = "Podaj login i hasło.";
        return;
    }

    authToken = btoa(username + ":" + password);

    fetch("api.php/movies", {
        headers: { "Authorization": "Basic " + authToken }
    })
    .then(res => {
        if (res.status === 401) {
            document.getElementById("loginError").textContent = "Niepoprawne dane logowania.";
            return;
        }

        document.getElementById("loginPanel").style.display = "none";
        document.getElementById("appPanel").style.display = "block";

        loadMovies();
    });
});

///wylogowanie
document.getElementById("logoutBtn").addEventListener("click", () => {
    authToken = null;

    document.getElementById("appPanel").style.display = "none";
    document.getElementById("loginPanel").style.display = "block";

    document.getElementById("loginUsername").value = "";
    document.getElementById("loginPassword").value = "";
});

//FETCH FILMOW
function loadMovies(filter = "") {
    let url = "api.php/movies";

    if (filter) url += "?min_rating=" + filter;

    fetch(url, {
        headers: { "Authorization": "Basic " + authToken }
    })
    .then(res => res.json())
    .then(data => renderMovies(data));
}

///tabela
function renderMovies(movies) {
    let html = `
        <div class="filter-inline">
            <input type="text" id="ratingFilter" placeholder="1-10">
            <button id="applyFilter">Filtruj</button>
        </div>

        <table>
            <tr>
                <th>ID</th>
                <th>Tytuł</th>
                <th>Rok</th>
                <th>Ocena</th>
                <th>Data obejrzenia</th>
                <th>Akcje</th>
            </tr>
    `;

    movies.forEach(m => {
        html += `
            <tr>
                <td>${m.id}</td>
                <td>${m.title}</td>
                <td>${m.year}</td>
                <td>${m.rating} ⭐</td>
                <td>${m.watch_date}</td>
                <td>
                    <div class="action-buttons">
                        <button class="editBtn" data-id="${m.id}">Edytuj</button>
                        <button class="deleteBtn" data-id="${m.id}">Usuń</button>
                    </div>
                </td>
            </tr>
        `;
    });

    html += "</table>";

    document.getElementById("movieList").innerHTML = html;

    document.getElementById("applyFilter").addEventListener("click", () => {
        const f = document.getElementById("ratingFilter").value.trim();
        loadMovies(f);
    });

    document.querySelectorAll(".editBtn").forEach(btn =>
        btn.addEventListener("click", () => loadMovieForEdit(btn.dataset.id))
    );

    document.querySelectorAll(".deleteBtn").forEach(btn =>
        btn.addEventListener("click", () => deleteMovie(btn.dataset.id))
    );
}

///DODANIE
document.getElementById("addMovieBtn").addEventListener("click", () => {
    const title = document.getElementById("title").value.trim();
    const year = document.getElementById("year").value.trim();
    const rating = document.getElementById("rating").value.trim();
    const watchDate = document.getElementById("watchDate").value;

    if (!title || !year || !rating || !watchDate) {
        alert("Wypełnij wszystkie pola.");
        return;
    }

    if (rating < 0 || rating > 10) {
        alert("Ocena musi być w zakresie 0–10.");
        return;
    }

    fetch("api.php/movies", {
        method: "POST",
        headers: {
            "Authorization": "Basic " + authToken,
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ title, year, rating, watch_date: watchDate })
    })
    .then(res => res.json())
    .then(() => {
        loadMovies();
        document.getElementById("title").value = "";
        document.getElementById("year").value = "";
        document.getElementById("rating").value = "";
        document.getElementById("watchDate").value = "";
    });
});

/// wczytanie do edycji
function loadMovieForEdit(id) {
    fetch("api.php/movies/" + id, {
        headers: { "Authorization": "Basic " + authToken }
    })
    .then(res => res.json())
    .then(m => {
        document.getElementById("editId").value = m.id;
        document.getElementById("editTitle").value = m.title;
        document.getElementById("editYear").value = m.year;
        document.getElementById("editRating").value = m.rating;
        document.getElementById("editWatchDate").value = m.watch_date;

        document.getElementById("editForm").style.display = "flex";
    });
}

///edit-save
document.getElementById("updateMovieBtn").addEventListener("click", () => {
    const id = document.getElementById("editId").value;
    const title = document.getElementById("editTitle").value.trim();
    const year = document.getElementById("editYear").value.trim();
    const rating = document.getElementById("editRating").value.trim();
    const watchDate = document.getElementById("editWatchDate").value;

    if (!title || !year || !rating || !watchDate) {
        alert("Wypełnij wszystkie pola.");
        return;
    }

    fetch("api.php/movies/" + id, {
        method: "PUT",
        headers: {
            "Authorization": "Basic " + authToken,
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ title, year, rating, watch_date: watchDate })
    })
    .then(() => {
        loadMovies();
        document.getElementById("editForm").style.display = "none";
    });
});

///anuluj edycje
document.getElementById("cancelEditBtn").addEventListener("click", () => {
    document.getElementById("editForm").style.display = "none";
});

///usuwanie
function deleteMovie(id) {
    if (!confirm("Na pewno usunąć ten film?")) return;

    fetch("api.php/movies/" + id, {
        method: "DELETE",
        headers: { "Authorization": "Basic " + authToken }
    })
    .then(() => loadMovies());
}
