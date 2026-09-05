<!DOCTYPE html>
<html dir="{{ $dir }}" @if (($theme ?? 'dark') !== 'auto') data-bs-theme="{{ $theme ?? 'dark' }}" @endif>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ $icon ?? 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'%3E%3Cdefs%3E%3CradialGradient id=\'sg\' cx=\'30%25\' cy=\'30%25\' r=\'70%25\'%3E%3Cstop offset=\'0%25\' stop-color=\'%232ecc71\'/%3E%3Cstop offset=\'40%25\' stop-color=\'%23249658\'/%3E%3Cstop offset=\'100%25\' stop-color=\'%231a6b3f\'/%3E%3C/radialGradient%3E%3C/defs%3E%3Crect x=\'4\' y=\'4\' width=\'92\' height=\'92\' rx=\'8\' fill=\'url(%23sg)\'/%3E%3Cg fill=\'%23fff\' transform=\'translate(28,28)\'%3E%3Crect x=\'0\' y=\'0\' width=\'19\' height=\'19\' rx=\'2\'/%3E%3Crect x=\'23\' y=\'0\' width=\'19\' height=\'19\' rx=\'2\'/%3E%3Crect x=\'0\' y=\'23\' width=\'19\' height=\'19\' rx=\'2\'/%3E%3Crect x=\'23\' y=\'23\' width=\'19\' height=\'19\' rx=\'2\'/%3E%3C/g%3E%3C/svg%3E' }}" />
    @if (filled($font))
        <style>
            @font-face {
                font-family: '{{ $fontFamily }}';
                src: url('data:font/{{ $fontMime ?? 'truetype' }};base64,{{ $font }}') format('{{ $fontFormat ?? 'truetype' }}');
                font-weight: normal;
                font-style: normal;
                font-display: block;
            }
        </style>
    @endif

    <style>
        :root {
            color-scheme: dark;
            --sn-bg: #151b24;
            --sn-surface: #1b222b;
            --sn-card: #262d36;
            --sn-border: #3a424c;
            --sn-hover: #343c46;
            --sn-text: #e6edf3;
            --sn-text-muted: #8b949e;
            --sn-accent: #58a6ff;

            --sn-ic-primary: #58a6ff;
            --sn-ic-success: #3fb950;
            --sn-ic-warning: #f0883e;
            --sn-ic-danger: #ff7b72;

            --sn-btn-danger: #da3633;
            --sn-btn-danger-rgb: 218, 54, 51;

            --sn-shadow-page: 0 0 0 1px #3a424c, 0 8px 28px rgba(1, 4, 9, 0.6);
            --sn-scroll-thumb: #3a424c;
            --sn-scroll-thumb-hover: #4d5561;

            --sn-radius: 4px;
            --sn-radius-lg: 8px;
            --sn-toolbar-h: 52px;
        }

        [data-bs-theme='light'] {
            color-scheme: light;
            --sn-bg: #fffefb;
            --sn-surface: #fbf8f2;
            --sn-card: #fffefc;
            --sn-border: #e4dbcf;
            --sn-hover: #d7e8f3;
            --sn-text: #302b24;
            --sn-text-muted: #746c61;
            --sn-accent: #0969da;

            --sn-ic-primary: #0969da;
            --sn-ic-success: #1a7f37;
            --sn-ic-warning: #9a6700;
            --sn-ic-danger: #d1242f;

            --sn-btn-danger: #d1242f;
            --sn-btn-danger-rgb: 209, 36, 47;

            --sn-shadow-page: 0 0 0 1px #e4dbcf, 0 8px 24px rgba(31, 35, 40, 0.12);
            --sn-scroll-thumb: #d5c8b5;
            --sn-scroll-thumb-hover: #c4b59f;
        }

        @media (prefers-color-scheme: light) {
            :root:not([data-bs-theme='dark']):not([data-bs-theme='light']) {
                color-scheme: light;
                --sn-bg: #fffefb;
                --sn-surface: #fbf8f2;
                --sn-card: #fffefc;
                --sn-border: #e4dbcf;
                --sn-hover: #d7e8f3;
                --sn-text: #302b24;
                --sn-text-muted: #746c61;
                --sn-accent: #0969da;

                --sn-ic-primary: #0969da;
                --sn-ic-success: #1a7f37;
                --sn-ic-warning: #9a6700;
                --sn-ic-danger: #d1242f;

                --sn-btn-danger: #d1242f;
                --sn-btn-danger-rgb: 209, 36, 47;

                --sn-shadow-page: 0 0 0 1px #e4dbcf, 0 8px 24px rgba(31, 35, 40, 0.12);
                --sn-scroll-thumb: #d5c8b5;
                --sn-scroll-thumb-hover: #c4b59f;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            background: var(--sn-bg);
        }

        body {
            height: 100vh;
            overflow: hidden;
            font-family: {!! $fontStack !!};
            background: var(--sn-bg);
            color: var(--sn-text);
        }

        #toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--sn-toolbar-h);
            z-index: 999;
            background: var(--sn-surface);
            border-bottom: 1px solid var(--sn-border);
            display: flex;
            align-items: center;
            padding: 0 14px;
            gap: 8px;
        }

        #doc-title {
            color: var(--sn-text);
            font-size: 14px;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
            min-width: 0;
            letter-spacing: 0.01em;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
            margin-inline-start: auto;
        }

        .btn {
            width: 34px;
            height: 34px;
            border-radius: var(--sn-radius);
            border: 1px solid var(--sn-border);
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.15s, border-color 0.15s, transform 0.1s;
            flex-shrink: 0;
            outline: none;
        }

        .btn:hover {
            background: var(--sn-hover);
            border-color: currentColor;
        }

        .btn:focus-visible {
            border-color: var(--sn-accent);
            box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.3);
        }

        .btn:active {
            transform: scale(0.94);
        }

        .btn svg {
            width: 20px;
            height: 20px;
            stroke-width: 1.8;
            display: block;
            pointer-events: none;
            overflow: visible;
        }

        .btn-print { color: var(--sn-ic-success); }
        .btn-download { color: var(--sn-ic-primary); }
        .btn-share { color: var(--sn-ic-warning); }

        #pdf-viewer-close-btn {
            color: var(--sn-ic-danger);
            animation: pulse-once 0.6s ease-out 0.3s both;
        }

        @keyframes pulse-once {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(var(--sn-btn-danger-rgb), 0.5);
            }
            50% {
                transform: scale(1.12);
                box-shadow: 0 0 0 8px rgba(var(--sn-btn-danger-rgb), 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(var(--sn-btn-danger-rgb), 0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            #pdf-viewer-close-btn { animation: none; }
            .btn { transition: none; }
        }

        #pdf-container {
            position: fixed;
            top: var(--sn-toolbar-h);
            left: 0;
            right: 0;
            bottom: 0;
            overflow-y: auto;
            overflow-x: hidden;
            background: var(--sn-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 16px 12px;
            gap: 12px;
            scrollbar-width: thin;
            scrollbar-color: var(--sn-scroll-thumb) transparent;
        }

        #pdf-container::-webkit-scrollbar {
            width: 8px;
        }

        #pdf-container::-webkit-scrollbar-track {
            background: transparent;
        }

        #pdf-container::-webkit-scrollbar-thumb {
            background: var(--sn-scroll-thumb);
            border-radius: var(--sn-radius);
        }

        #pdf-container::-webkit-scrollbar-thumb:hover {
            background: var(--sn-scroll-thumb-hover);
        }

        .pdf-page {
            display: block;
            box-shadow: var(--sn-shadow-page);
            flex-shrink: 0;
            background: #fff;
            max-width: 100%;
        }

        #loading,
        #error-screen {
            position: fixed;
            top: var(--sn-toolbar-h);
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--sn-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            z-index: 10;
        }

        #loading-spinner {
            width: 28px;
            height: 28px;
            border: 2px solid var(--sn-border);
            border-top-color: var(--sn-accent);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        #loading-text {
            color: var(--sn-text-muted);
            font-size: 12px;
            letter-spacing: 0.04em;
        }

        #error-screen {
            display: none;
            color: var(--sn-ic-danger);
            font-size: 13px;
        }

        #error-screen svg {
            width: 28px;
            height: 28px;
            stroke-width: 1.8;
        }
    </style>
</head>

<body>
    <div id="toolbar">
        <button id="pdf-viewer-close-btn" class="btn" type="button" aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>

        <span id="doc-title">{{ $title }}</span>

        <div class="actions">
            <button id="btn-print" class="btn btn-print" type="button" aria-label="Print">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v5" />
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                    <rect x="6" y="15" width="12" height="6" rx="1" />
                    <circle cx="18" cy="12" r="0.9" fill="currentColor" stroke="none" />
                </svg>
            </button>

            <button id="btn-download" class="btn btn-download" type="button" aria-label="Download">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <path d="M12 3v12" />
                    <path d="M7.5 10.5 12 15l4.5-4.5" />
                </svg>
            </button>

            <button id="btn-share" class="btn btn-share" type="button" aria-label="Share">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4" />
                    <circle cx="18" cy="5" r="3" />
                    <circle cx="6" cy="12" r="3" />
                    <circle cx="18" cy="19" r="3" />
                </svg>
            </button>
        </div>
    </div>

    <div id="loading">
        <div id="loading-spinner"></div>
        <div id="loading-text">Loading…</div>
    </div>

    <div id="error-screen">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
        </svg>
        <span>Failed to load document.</span>
    </div>

    <div id="pdf-container"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        var CMAP_URL = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/';
        var STANDARD_FONT = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/standard_fonts/';

        var PdfViewerState = {
            filename: @json($filename),
            blob: null,
            url: null,
            pdfDoc: null,
        };

        var DocumentElements = {
            get container() { return document.getElementById('pdf-container'); },
            get loading() { return document.getElementById('loading'); },
            get loadingText() { return document.getElementById('loading-text'); },
            get errorScreen() { return document.getElementById('error-screen'); },
            get btnPrint() { return document.getElementById('btn-print'); },
            get btnDownload() { return document.getElementById('btn-download'); },
            get btnShare() { return document.getElementById('btn-share'); },
            get btnClose() { return document.getElementById('pdf-viewer-close-btn'); },
        };

        var PdfDecoder = {
            fromBase64: function (b64) {
                var binary = atob(b64);
                var bytes = new Uint8Array(binary.length);
                for (var i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
                return bytes.buffer;
            },
            toBlob: function (buf) {
                return new Blob([buf], { type: 'application/pdf' });
            },
            toObjectUrl: function (blob) {
                return URL.createObjectURL(blob);
            },
        };

        var PdfRenderer = {
            displayWidth: function (page) {
                var available = DocumentElements.container.clientWidth - 24;
                var pageWidth = page.getViewport({ scale: 1 }).width;

                if (window.innerWidth < 768) {
                    return available;
                }

                return Math.min(pageWidth * 1.5, available);
            },

            renderPage: function (num) {
                return PdfViewerState.pdfDoc.getPage(num).then(function (page) {
                    var dpr = window.devicePixelRatio || 1;
                    var cssWidth = PdfRenderer.displayWidth(page);
                    var naturalWidth = page.getViewport({ scale: 1 }).width;

                    var viewport = page.getViewport({ scale: (cssWidth / naturalWidth) * dpr });

                    var canvas = document.createElement('canvas');
                    canvas.className = 'pdf-page';
                    canvas.width = Math.floor(viewport.width);
                    canvas.height = Math.floor(viewport.height);
                    canvas.style.width = cssWidth + 'px';
                    canvas.style.height = Math.floor(viewport.height / dpr) + 'px';

                    DocumentElements.container.appendChild(canvas);

                    var ctx = canvas.getContext('2d', { alpha: false });
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';

                    return page.render({
                        canvasContext: ctx,
                        viewport: viewport,
                    }).promise;
                });
            },

            renderAll: function () {
                var total = PdfViewerState.pdfDoc.numPages;
                var promise = Promise.resolve();

                DocumentElements.container.innerHTML = '';

                for (var i = 1; i <= total; i++) {
                    (function (num) {
                        promise = promise.then(function () {
                            DocumentElements.loadingText.textContent = 'Page ' + num + ' of ' + total;
                            return PdfRenderer.renderPage(num);
                        });
                    })(i);
                }

                return promise;
            },
        };

        var PdfActions = {
            download: function () {
                var a = document.createElement('a');
                a.href = PdfViewerState.url;
                a.download = PdfViewerState.filename;
                a.click();
            },

            print: function () {
                var iframe = document.createElement('iframe');
                iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;visibility:hidden;';
                iframe.src = PdfViewerState.url;
                document.body.appendChild(iframe);
                iframe.onload = function () {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                    setTimeout(function () {
                        document.body.removeChild(iframe);
                    }, 60000);
                };
            },

            share: function () {
                var file = new File([PdfViewerState.blob], PdfViewerState.filename, {
                    type: 'application/pdf',
                });

                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    navigator
                        .share({ files: [file], title: PdfViewerState.filename })
                        .catch(function (e) {
                            if (e.name !== 'AbortError') console.error('Share failed', e);
                        });
                    return;
                }

                if (navigator.clipboard) {
                    navigator.clipboard.writeText(location.href);
                }
            },

            close: function () {
                if (window.self === window.top) {
                    window.close();
                    return;
                }

                try {
                    if (window.parent.jQuery) {
                        window.parent.jQuery('.modal').modal('hide');
                        return;
                    }
                } catch (e) {
                }

                window.parent.postMessage({ type: 'pdf-viewer:close' }, '*');
            },
        };

        var PdfViewer = {
            initialize: function () {
                var base64 = @json($base64);
                var buffer = PdfDecoder.fromBase64(base64);

                PdfViewerState.blob = PdfDecoder.toBlob(buffer);
                PdfViewerState.url = PdfDecoder.toObjectUrl(PdfViewerState.blob);

                pdfjsLib
                    .getDocument({
                        data: buffer,
                        cMapUrl: CMAP_URL,
                        cMapPacked: true,
                        standardFontDataUrl: STANDARD_FONT,
                    })
                    .promise.then(function (pdfDoc) {
                        PdfViewerState.pdfDoc = pdfDoc;
                        return PdfRenderer.renderAll();
                    })
                    .then(function () {
                        DocumentElements.loading.style.display = 'none';
                        DocumentElements.btnPrint.addEventListener('click', PdfActions.print);
                        DocumentElements.btnDownload.addEventListener('click', PdfActions.download);
                        DocumentElements.btnShare.addEventListener('click', PdfActions.share);
                    })
                    .catch(function (err) {
                        console.error('PDF error', err);
                        DocumentElements.loading.style.display = 'none';
                        DocumentElements.errorScreen.style.display = 'flex';
                    });
            },
        };

        var ApplicationUI = {
            setupPrintButton: function () {
                var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

                if (isMobile) {
                    DocumentElements.btnPrint.style.display = 'none';
                }
            },

            setupResizeHandler: function () {
                var lastWidth = window.innerWidth;
                var timer = null;

                window.addEventListener('resize', function () {
                    if (window.innerWidth === lastWidth || !PdfViewerState.pdfDoc) {
                        return;
                    }

                    lastWidth = window.innerWidth;
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        PdfRenderer.renderAll();
                    }, 200);
                });
            },

            setupCleanup: function () {
                window.addEventListener('pagehide', function () {
                    if (PdfViewerState.url) {
                        URL.revokeObjectURL(PdfViewerState.url);
                    }
                });
            },
        };

        var Application = {
            initialize: function () {
                PdfViewer.initialize();
                ApplicationUI.setupPrintButton();
                ApplicationUI.setupResizeHandler();
                ApplicationUI.setupCleanup();
            },
        };

        document.addEventListener('DOMContentLoaded', function () {
            Application.initialize();
            DocumentElements.btnClose.addEventListener('click', PdfActions.close);
        });
    </script>
</body>
</html>
