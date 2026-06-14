import grapesjs from 'grapesjs';
import newsletterPreset from 'grapesjs-preset-newsletter';
import 'grapesjs/dist/css/grapes.min.css';

const editors = {};

window.NettMailGrapesJS = {
    mount(elementId, { html, projectData } = {}) {
        const editor = grapesjs.init({
            container: `#${elementId}`,
            height: '600px',
            fromElement: false,
            storageManager: false,
            plugins: [newsletterPreset],
        });

        if (projectData) {
            editor.loadProjectData(projectData);
        } else if (html) {
            editor.setComponents(html);
        }

        editors[elementId] = editor;
    },

    export(elementId) {
        const editor = editors[elementId];

        if (!editor) {
            throw new Error(`NettMail: editor "${elementId}" is not ready.`);
        }

        return {
            html: `<style>${editor.getCss()}</style>${editor.getHtml()}`,
            projectData: editor.getProjectData(),
        };
    },
};
