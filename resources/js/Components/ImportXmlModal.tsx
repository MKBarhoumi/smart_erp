import { useState, useRef, type ChangeEvent, type DragEvent } from 'react';
import { router } from '@inertiajs/react';
import { Modal } from '@/Components/ui/Modal';
import { Button } from '@/Components/ui/Button';
import { formatTND } from '@/utils/format';

interface ImportXmlModalProps {
    show: boolean;
    onClose: () => void;
}

interface ParsedLine {
    line_id: string;
    item_code?: string;
    quantity: string;
    description: string;
    unit_price: string;
    line_amount: string;
    tax_rate?: string;
}

interface ParsedData {
    invoice_id: string;
    issue_date: string;
    invoice_type: string;
    sender_name: string;
    sender_tax_id: string;
    receiver_name: string;
    receiver_tax_id: string;
    total_ht: string;
    total_tva: string;
    total_ttc: string;
    lines: ParsedLine[];
}

type Step = 'upload' | 'preview' | 'confirm';

export function ImportXmlModal({ show, onClose }: ImportXmlModalProps) {
    const [step, setStep] = useState<Step>('upload');
    const [file, setFile] = useState<File | null>(null);
    const [isDragOver, setIsDragOver] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [parsedData, setParsedData] = useState<ParsedData | null>(null);
    const [errors, setErrors] = useState<string[]>([]);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const resetState = () => {
        setStep('upload');
        setFile(null);
        setParsedData(null);
        setErrors([]);
        setIsLoading(false);
    };

    const handleClose = () => {
        resetState();
        onClose();
    };

    const handleDrop = (e: DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragOver(false);
        const droppedFile = e.dataTransfer.files[0];
        if (droppedFile && droppedFile.name.endsWith('.xml')) {
            setFile(droppedFile);
            setErrors([]);
        } else {
            setErrors(['Please upload a valid XML file.']);
        }
    };

    const handleDragOver = (e: DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragOver(true);
    };

    const handleDragLeave = () => {
        setIsDragOver(false);
    };

    const handleFileSelect = (e: ChangeEvent<HTMLInputElement>) => {
        const selectedFile = e.target.files?.[0];
        if (selectedFile) {
            if (selectedFile.name.endsWith('.xml')) {
                setFile(selectedFile);
                setErrors([]);
            } else {
                setErrors(['Please upload a valid XML file.']);
            }
        }
    };

    const handleParseXml = async () => {
        if (!file) return;

        setIsLoading(true);
        setErrors([]);

        const formData = new FormData();
        formData.append('file', file);

        // Get CSRF token from meta tag or cookie
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        try {
            const response = await fetch('/invoices-import/parse', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const result = await response.json();

            if (result.success) {
                setParsedData(result.data);
                setStep('preview');
            } else {
                setErrors(result.errors || ['Failed to parse XML file.']);
            }
        } catch (error) {
            setErrors(['An error occurred while parsing the file.']);
        } finally {
            setIsLoading(false);
        }
    };

    const handleImport = async () => {
        if (!parsedData) return;

        setIsLoading(true);
        setErrors([]);

        // Get CSRF token from meta tag or cookie
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        try {
            const response = await fetch('/invoices-import/store', {
                method: 'POST',
                body: JSON.stringify(parsedData),
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            const result = await response.json();

            if (result.success) {
                setStep('confirm');
                // Refresh the page after a short delay
                setTimeout(() => {
                    handleClose();
                    router.reload();
                }, 2000);
            } else {
                setErrors([result.message || 'Failed to import invoice.']);
            }
        } catch (error) {
            setErrors(['An error occurred while importing the invoice.']);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <Modal show={show} onClose={handleClose} maxWidth="xl">
            <div className="p-6">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                        <div className="p-2 rounded-xl bg-gradient-to-br from-user-600 to-purple-500 text-white">
                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                        </div>
                        <div>
                            <h2 className="text-xl font-bold text-gray-900">Import XML Invoice</h2>
                            <p className="text-sm text-gray-500">Import a TEIF-compliant XML invoice file</p>
                        </div>
                    </div>
                    <button onClick={handleClose} className="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Steps indicator */}
                <div className="flex items-center justify-center mb-8">
                    <div className="flex items-center gap-3">
                        <div className={`flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold ${step === 'upload' ? 'bg-user-600 text-white' : 'bg-user-100 text-user-600'}`}>1</div>
                        <span className={`text-sm font-medium ${step === 'upload' ? 'text-user-600' : 'text-gray-500'}`}>Upload</span>
                        <div className="w-12 h-0.5 bg-gray-200" />
                        <div className={`flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold ${step === 'preview' ? 'bg-user-600 text-white' : step === 'confirm' ? 'bg-user-100 text-user-600' : 'bg-gray-200 text-gray-400'}`}>2</div>
                        <span className={`text-sm font-medium ${step === 'preview' ? 'text-user-600' : 'text-gray-500'}`}>Preview</span>
                        <div className="w-12 h-0.5 bg-gray-200" />
                        <div className={`flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold ${step === 'confirm' ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-400'}`}>3</div>
                        <span className={`text-sm font-medium ${step === 'confirm' ? 'text-emerald-600' : 'text-gray-500'}`}>Confirm</span>
                    </div>
                </div>

                {/* Step Content */}
                {step === 'upload' && (
                    <div className="space-y-4">
                        {/* Drop zone */}
                        <div
                            onDrop={handleDrop}
                            onDragOver={handleDragOver}
                            onDragLeave={handleDragLeave}
                            onClick={() => fileInputRef.current?.click()}
                            className={`border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-all ${
                                isDragOver ? 'border-user-500 bg-user-50' : file ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300 hover:border-user-400 hover:bg-gray-50'
                            }`}
                        >
                            <input
                                type="file"
                                ref={fileInputRef}
                                onChange={handleFileSelect}
                                accept=".xml"
                                className="hidden"
                            />
                            {file ? (
                                <div className="flex items-center justify-center gap-3">
                                    <div className="p-3 rounded-xl bg-emerald-100">
                                        <svg className="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div className="text-left">
                                        <p className="font-semibold text-gray-900">{file.name}</p>
                                        <p className="text-sm text-gray-500">{(file.size / 1024).toFixed(1)} KB</p>
                                    </div>
                                    <button
                                        onClick={(e) => { e.stopPropagation(); setFile(null); }}
                                        className="p-1 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50"
                                    >
                                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            ) : (
                                <>
                                    <div className="mx-auto w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                                        <svg className="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </div>
                                    <p className="text-gray-900 font-medium">Drop your XML file here or click to browse</p>
                                    <p className="text-sm text-gray-500 mt-1">Accepts .xml files up to 5MB</p>
                                </>
                            )}
                        </div>

                        {/* Errors */}
                        {errors.length > 0 && (
                            <div className="p-4 rounded-xl bg-red-50 border border-red-200">
                                <div className="flex items-start gap-3">
                                    <svg className="w-5 h-5 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                    <div>
                                        {errors.map((error, idx) => (
                                            <p key={idx} className="text-sm text-red-700">{error}</p>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Actions */}
                        <div className="flex justify-end gap-3 pt-4">
                            <Button variant="secondary" onClick={handleClose}>Cancel</Button>
                            <Button onClick={handleParseXml} disabled={!file} loading={isLoading}>
                                Validate & Preview
                            </Button>
                        </div>
                    </div>
                )}

                {step === 'preview' && parsedData && (
                    <div className="space-y-4">
                        {/* Validation status */}
                        <div className="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center gap-3">
                            <svg className="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span className="text-sm font-medium text-emerald-700">XML validation passed</span>
                        </div>

                        {/* Preview table */}
                        <div className="bg-gray-50 rounded-xl p-4">
                            <h3 className="text-sm font-semibold text-gray-700 mb-3">Extracted Fields</h3>
                            <table className="w-full text-sm">
                                <tbody className="divide-y divide-gray-200">
                                    <tr>
                                        <td className="py-2 text-gray-500 w-1/3">Invoice ID</td>
                                        <td className="py-2 font-mono font-medium text-gray-900">{parsedData.invoice_id}</td>
                                    </tr>
                                    <tr>
                                        <td className="py-2 text-gray-500">Invoice Type</td>
                                        <td className="py-2 font-medium text-gray-900">{parsedData.invoice_type}</td>
                                    </tr>
                                    <tr>
                                        <td className="py-2 text-gray-500">Sender</td>
                                        <td className="py-2 font-medium text-gray-900">{parsedData.sender_name || parsedData.sender_tax_id}</td>
                                    </tr>
                                    <tr>
                                        <td className="py-2 text-gray-500">Receiver</td>
                                        <td className="py-2 font-medium text-gray-900">{parsedData.receiver_name || parsedData.receiver_tax_id}</td>
                                    </tr>
                                    <tr>
                                        <td className="py-2 text-gray-500">Date</td>
                                        <td className="py-2 font-medium text-gray-900">{parsedData.issue_date}</td>
                                    </tr>
                                    <tr>
                                        <td className="py-2 text-gray-500">Total HT</td>
                                        <td className="py-2 font-medium text-gray-900">{formatTND(parsedData.total_ht)}</td>
                                    </tr>
                                    <tr>
                                        <td className="py-2 text-gray-500">Total TVA</td>
                                        <td className="py-2 font-medium text-gray-900">{formatTND(parsedData.total_tva)}</td>
                                    </tr>
                                    <tr>
                                        <td className="py-2 text-gray-500">Total TTC</td>
                                        <td className="py-2 font-semibold text-user-600">{formatTND(parsedData.total_ttc)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {/* Invoice lines */}
                        {parsedData.lines.length > 0 && (
                            <div className="bg-gray-50 rounded-xl p-4">
                                <h3 className="text-sm font-semibold text-gray-700 mb-3">Invoice Lines ({parsedData.lines.length})</h3>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-gray-200">
                                                <th className="py-2 text-left text-xs font-semibold text-gray-500">#</th>
                                                <th className="py-2 text-left text-xs font-semibold text-gray-500">Description</th>
                                                <th className="py-2 text-right text-xs font-semibold text-gray-500">Qty</th>
                                                <th className="py-2 text-right text-xs font-semibold text-gray-500">Unit Price</th>
                                                <th className="py-2 text-right text-xs font-semibold text-gray-500">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100">
                                            {parsedData.lines.map((line, idx) => (
                                                <tr key={idx}>
                                                    <td className="py-2 text-gray-500">{line.line_id || idx + 1}</td>
                                                    <td className="py-2 text-gray-900">{line.description || '-'}</td>
                                                    <td className="py-2 text-right text-gray-900">{line.quantity}</td>
                                                    <td className="py-2 text-right text-gray-900">{formatTND(line.unit_price)}</td>
                                                    <td className="py-2 text-right font-medium text-gray-900">{formatTND(line.line_amount)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* Errors */}
                        {errors.length > 0 && (
                            <div className="p-4 rounded-xl bg-red-50 border border-red-200">
                                <div className="flex items-start gap-3">
                                    <svg className="w-5 h-5 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                    <div>
                                        {errors.map((error, idx) => (
                                            <p key={idx} className="text-sm text-red-700">{error}</p>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Actions */}
                        <div className="flex justify-end gap-3 pt-4">
                            <Button variant="secondary" onClick={() => setStep('upload')}>Back</Button>
                            <Button variant="secondary" onClick={handleClose}>Cancel</Button>
                            <Button onClick={handleImport} loading={isLoading}>
                                Import Invoice
                            </Button>
                        </div>
                    </div>
                )}

                {step === 'confirm' && (
                    <div className="text-center py-8">
                        <div className="mx-auto w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mb-4">
                            <svg className="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 className="text-xl font-bold text-gray-900 mb-2">Invoice Imported Successfully!</h3>
                        <p className="text-gray-500">The invoice has been imported as a draft. Redirecting...</p>
                    </div>
                )}
            </div>
        </Modal>
    );
}
