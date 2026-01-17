/* =========================================
   FEEDLEARN RESUME MANAGER
   Handles auto-saving and restoring activity progress.
   ========================================= */

const ResumeManager = {
    storageKey: 'feedlearn_progress',

    init() {
        const path = window.location.pathname;

        // 1. Handle Redirector Page
        if (path.includes('resume.php')) {
            this.handleRedirect();
            return;
        }

        // 2. Identify Activity Type
        let type = null;
        if (path.includes('quiz.php')) type = 'quiz';
        else if (path.includes('writing.php')) type = 'writing';

        if (!type) return;

        // 3. Setup Auto-Save & Restore
        // Check if we should restore (either via URL param or just auto-restore for convenience)
        // User asked "kaldığı yerden devam etsin". Ideally always restore if data exists?
        // Let's restore if data exists AND it is recent (< 24h).
        this.restoreActivity(type);
        this.setupAutoSave(type);

        // 4. Clear on Submit
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', () => {
                this.clearProgress(type);
            });
        }
    },

    getData() {
        try {
            return JSON.parse(localStorage.getItem(this.storageKey)) || {};
        } catch (e) {
            return {};
        }
    },

    saveData(type, payload) {
        const data = this.getData();
        data[type] = {
            timestamp: Date.now(),
            payload: payload
        };
        localStorage.setItem(this.storageKey, JSON.stringify(data));
    },

    clearProgress(type) {
        const data = this.getData();
        delete data[type];
        localStorage.setItem(this.storageKey, JSON.stringify(data));
    },

    setupAutoSave(type) {
        if (type === 'quiz') {
            const inputs = document.querySelectorAll('input[type="radio"]');
            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    // Save all checked radios
                    const answers = {};
                    document.querySelectorAll('input[type="radio"]:checked').forEach(r => {
                        answers[r.name] = r.value;
                    });
                    this.saveData('quiz', answers);
                });
            });
        } else if (type === 'writing') {
            const textarea = document.querySelector('textarea[name="writing_text"]');
            if (textarea) {
                textarea.addEventListener('input', () => {
                    const promptText = document.querySelector('.muted') ? document.querySelector('.muted').innerText : '';
                    this.saveData('writing', {
                        text: textarea.value,
                        prompt: promptText // Optional: Try to verify prompt match later
                    });
                });
            }
        }
    },

    restoreActivity(type) {
        const data = this.getData()[type];
        if (!data || !data.payload) return;

        // Use a small timeout to ensure DOM is ready and prevent potential conflicts
        setTimeout(() => {
            if (type === 'quiz') {
                const answers = data.payload;
                Object.keys(answers).forEach(name => {
                    const val = answers[name];
                    const radio = document.querySelector(`input[name="${name}"][value="${val}"]`);
                    if (radio) radio.checked = true;
                });
            } else if (type === 'writing') {
                const textarea = document.querySelector('textarea[name="writing_text"]');
                if (textarea) {
                    textarea.value = data.payload.text || '';
                    // Ideally we might warn if prompt is different, but for now we just fill the text
                }
            }
        }, 100);
    },

    handleRedirect() {
        const data = this.getData();
        let latestType = null;
        let latestTime = 0;

        // Find most recent activity
        Object.keys(data).forEach(type => {
            if (data[type].timestamp > latestTime) {
                latestTime = data[type].timestamp;
                latestType = type;
            }
        });

        const container = document.querySelector('.card');
        if (latestType && (Date.now() - latestTime < 7 * 24 * 60 * 60 * 1000)) { // 1 week expiry
            // Redirect
            if (container) container.innerHTML = `<p class="muted">Resuming ${latestType}...</p>`;
            window.location.href = `../activity/${latestType}.php?resume=1`;
        } else {
            // No data
            if (container) {
                container.innerHTML = `
                    <h2>No Unfinished Activity</h2>
                    <p class="muted">We couldn't find any saved progress to resume.</p>
                    <a class="btn btn-primary" href="../dashboard.php">Go to Dashboard</a>
                `;
            }
        }
    }
};

document.addEventListener('DOMContentLoaded', () => ResumeManager.init());
