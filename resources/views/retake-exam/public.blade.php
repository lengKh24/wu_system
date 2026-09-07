<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retake Exam Registration</title>
    {{-- Plain functional page, no build step, no styling effort spent —
         backend endpoints are the point here; restyle later. --}}
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; color: #111; }
        h1 { font-size: 1.25rem; }
        h2 { font-size: 1.05rem; margin-top: 2rem; }
        label { display: block; margin-top: 0.75rem; font-weight: bold; }
        input[type=text], input[type=date] { display: block; width: 100%; padding: 0.5rem; margin-top: 0.25rem; box-sizing: border-box; }
        button { margin-top: 1rem; padding: 0.5rem 1rem; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
        th, td { border: 1px solid #ccc; padding: 0.4rem 0.5rem; text-align: left; font-size: 0.9rem; }
        #errorBox { color: #b00; margin-top: 1rem; display: none; }
        #okBox { color: #076; margin-top: 1rem; display: none; }
        .batch-block { border: 1px solid #ddd; padding: 1rem; margin-top: 1rem; }
        .hidden { display: none; }
        .muted { color: #666; font-size: 0.85rem; }
    </style>
</head>
<body>

    <h1>Retake Exam Registration / ការចុះឈ្មោះប្រឡងសង</h1>
    <p class="muted">Enter your student code and date of birth to see and confirm your retake registration.</p>

    <div id="lookupForm">
        <label for="code">Student Code</label>
        <input type="text" id="code" autocomplete="off">

        <label for="dob">Date of Birth</label>
        <input type="date" id="dob">

        <button id="lookupBtn" type="button">Look Up</button>
    </div>

    <div id="errorBox"></div>
    <div id="okBox"></div>

    <div id="resultBox" class="hidden">
        <h2 id="studentName"></h2>

        <div id="pendingBatches"></div>

        <div id="confirmSection" class="hidden">
            <button id="saveSelectionsBtn" type="button">Save Selections</button>
            <button id="confirmBtn" type="button">Confirm Registration</button>
            <p class="muted">Saving selections does not lock anything in — you can change your mind and look up again later. Confirming locks in whatever is checked above and cannot be undone here; please pay at the Student Affairs office afterward to complete registration.</p>
        </div>

        <div id="confirmedSection" class="hidden">
            <h2>Already Confirmed</h2>
            <table>
                <thead><tr><th>Term</th><th>Exam Type</th><th>Subject</th><th>Registered At</th><th>Payment</th><th>Outcome</th></tr></thead>
                <tbody id="confirmedRows"></tbody>
            </table>
        </div>
    </div>

    <script>
        (function () {
            var apiBase = '/api/v1/retake-exam';
            var currentCode = null;
            var currentDob = null;

            var lookupBtn = document.getElementById('lookupBtn');
            var saveBtn = document.getElementById('saveSelectionsBtn');
            var confirmBtn = document.getElementById('confirmBtn');

            function showError(message) {
                var box = document.getElementById('errorBox');
                box.textContent = message;
                box.style.display = 'block';
                document.getElementById('okBox').style.display = 'none';
            }

            function showOk(message) {
                var box = document.getElementById('okBox');
                box.textContent = message;
                box.style.display = 'block';
                document.getElementById('errorBox').style.display = 'none';
            }

            function clearMessages() {
                document.getElementById('errorBox').style.display = 'none';
                document.getElementById('okBox').style.display = 'none';
            }

            function post(path, body) {
                return fetch(apiBase + path, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(body)
                }).then(function (res) {
                    return res.json().then(function (json) {
                        if (!res.ok || !json.success) {
                            throw new Error(json.message || 'Something went wrong.');
                        }
                        return json.data;
                    });
                });
            }

            function subjectLabel(row) {
                return (row.subject && row.subject.name) ? row.subject.name : ('Subject #' + row.id);
            }

            function renderPending(data) {
                var container = document.getElementById('pendingBatches');
                container.innerHTML = '';

                if (!data.pending_batches || data.pending_batches.length === 0) {
                    container.innerHTML = '<p class="muted">No pending registration found for this student.</p>';
                    document.getElementById('confirmSection').classList.add('hidden');
                    return;
                }

                data.pending_batches.forEach(function (batch) {
                    var block = document.createElement('div');
                    block.className = 'batch-block';

                    var title = document.createElement('strong');
                    title.textContent = (batch.term || '') + ' — ' + (batch.exam_type || '');
                    block.appendChild(title);

                    var table = document.createElement('table');
                    table.innerHTML = '<thead><tr><th>Register?</th><th>Subject</th></tr></thead>';
                    var tbody = document.createElement('tbody');

                    var allRows = (batch.will_register || []).concat(batch.will_not_register || []);
                    allRows.forEach(function (row) {
                        var tr = document.createElement('tr');

                        var tdCheck = document.createElement('td');
                        var checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.checked = !!row.is_selected;
                        checkbox.dataset.registrationId = row.id;
                        tdCheck.appendChild(checkbox);
                        tr.appendChild(tdCheck);

                        var tdSubject = document.createElement('td');
                        tdSubject.textContent = subjectLabel(row);
                        tr.appendChild(tdSubject);

                        tbody.appendChild(tr);
                    });

                    table.appendChild(tbody);
                    block.appendChild(table);
                    container.appendChild(block);
                });

                document.getElementById('confirmSection').classList.remove('hidden');
            }

            function renderConfirmed(data) {
                var section = document.getElementById('confirmedSection');
                var rows = data.confirmed_registrations || [];

                if (rows.length === 0) {
                    section.classList.add('hidden');
                    return;
                }

                var tbody = document.getElementById('confirmedRows');
                tbody.innerHTML = '';

                rows.forEach(function (row) {
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + ((row.term && row.term.title) || '') + '</td>' +
                        '<td>' + ((row.exam_type && row.exam_type.name) || '') + '</td>' +
                        '<td>' + subjectLabel(row) + '</td>' +
                        '<td>' + (row.registered_at || '') + '</td>' +
                        '<td>' + (row.payment_status || '') + '</td>' +
                        '<td>' + (row.outcome || '') + '</td>';
                    tbody.appendChild(tr);
                });

                section.classList.remove('hidden');
            }

            function renderStudent(data) {
                var s = data.student;
                var name = [s.first_name_kh, s.last_name_kh].filter(Boolean).join(' ')
                    || [s.first_name, s.last_name].filter(Boolean).join(' ')
                    || s.code;
                document.getElementById('studentName').textContent = name + ' (' + s.code + ')';
            }

            function renderAll(data) {
                document.getElementById('resultBox').classList.remove('hidden');
                renderStudent(data);
                renderPending(data);
                renderConfirmed(data);
            }

            lookupBtn.addEventListener('click', function () {
                clearMessages();
                currentCode = document.getElementById('code').value.trim();
                currentDob = document.getElementById('dob').value;

                if (!currentCode || !currentDob) {
                    showError('Please enter both student code and date of birth.');
                    return;
                }

                post('/lookup', { code: currentCode, dob: currentDob })
                    .then(renderAll)
                    .catch(function (err) { showError(err.message); });
            });

            saveBtn.addEventListener('click', function () {
                clearMessages();
                var selections = Array.prototype.map.call(
                    document.querySelectorAll('#pendingBatches input[type=checkbox]'),
                    function (el) {
                        return { id: parseInt(el.dataset.registrationId, 10), is_selected: el.checked };
                    }
                );

                post('/select', { code: currentCode, dob: currentDob, selections: selections })
                    .then(function (data) {
                        showOk('Selections saved.');
                        renderAll(data);
                    })
                    .catch(function (err) { showError(err.message); });
            });

            confirmBtn.addEventListener('click', function () {
                clearMessages();

                if (!window.confirm('Confirm registration with the subjects currently checked? This cannot be undone here.')) {
                    return;
                }

                post('/confirm', { code: currentCode, dob: currentDob })
                    .then(function (data) {
                        showOk('Registration confirmed. Please proceed to Student Affairs for payment.');
                        renderAll(data);
                    })
                    .catch(function (err) { showError(err.message); });
            });
        })();
    </script>
</body>
</html>
