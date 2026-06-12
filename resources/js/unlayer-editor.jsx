import React, { useRef, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import EmailEditor from 'react-email-editor';

const editors = {};

function EditorApp({ elementId, design, mergeTags, projectId }) {
    const emailEditorRef = useRef(null);

    const onReady = () => {
        const unlayer = emailEditorRef.current?.editor;

        if (!unlayer) {
            return;
        }

        editors[elementId] = unlayer;

        if (design) {
            unlayer.loadDesign(design);
        }
    };

    return (
        <EmailEditor
            ref={emailEditorRef}
            onReady={onReady}
            minHeight="600px"
            projectId={projectId}
            options={{ mergeTags }}
        />
    );
}

window.NettMailUnlayer = {
    mount(elementId, { design, mergeTags, projectId } = {}) {
        const container = document.getElementById(elementId);

        if (!container) {
            return;
        }

        createRoot(container).render(
            <EditorApp elementId={elementId} design={design} mergeTags={mergeTags} projectId={projectId} />
        );
    },

    export(elementId) {
        return new Promise((resolve, reject) => {
            const editor = editors[elementId];

            if (!editor) {
                reject(new Error(`NettMail: editor "${elementId}" is not ready.`));

                return;
            }

            editor.exportHtml((data) => resolve(data));
        });
    },
};
