// Helper to submit a travel idea form to Public/api/travel_ideas/create_idea.php
/**

 * Author: Ramal Gurung
 * Group: L5CG6
 */
async function postTravelIdea(formEl, options = {}) {
    const API = options.apiBase || 'api/travel_ideas/create_idea.php';
    const submitBtn = formEl.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerText : null;
    const formData = options.formData instanceof FormData ? options.formData : new FormData(formEl);

    try {
        if (submitBtn) { submitBtn.innerText = 'Submitting...'; submitBtn.disabled = true; }
        if (formData.has('location') && !formData.has('province')) {
            const locationValue = formData.get('location');
            if (locationValue) {
                formData.set('province', locationValue);
            }
        }
        const res = await fetch(API, { method: 'POST', body: formData });
        const responseText = await res.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Invalid JSON response from travel ideas API:', res.status, responseText);
            return { success: false, message: 'Server returned invalid response', status: res.status, raw: responseText };
        }
        return data;
    } catch (err) {
        console.error('postTravelIdea error', err);
        return { success: false, message: 'Network error' };
    } finally {
        if (submitBtn) { submitBtn.disabled = false; if (originalText) submitBtn.innerText = originalText; }
    }
}

// Example usage:
// document.getElementById('travelIdeaForm').addEventListener('submit', async (e) => {
//   e.preventDefault();
//   const data = await postTravelIdea(e.target);
//   if (data.success) alert('Posted'); else alert(data.message || 'Failed');
// });
