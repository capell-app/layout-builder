<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta
            name="viewport"
            content="width=device-width,initial-scale=1"
        />
        <title>{{ $formName }} submission</title>
    </head>
    <body
        style="
            margin: 0;
            padding: 0;
            background: #f4f5f7;
            font-family: Helvetica, Arial, sans-serif;
            color: #1f2937;
        "
    >
        <table
            role="presentation"
            width="100%"
            cellpadding="0"
            cellspacing="0"
            style="background: #f4f5f7; padding: 24px 0"
        >
            <tr>
                <td align="center">
                    <table
                        role="presentation"
                        width="560"
                        cellpadding="0"
                        cellspacing="0"
                        style="
                            max-width: 560px;
                            background: #ffffff;
                            border-radius: 8px;
                            overflow: hidden;
                        "
                    >
                        <tr>
                            <td
                                style="
                                    padding: 24px 32px;
                                    background: #111827;
                                    color: #ffffff;
                                "
                            >
                                <h1
                                    style="
                                        margin: 0;
                                        font-size: 18px;
                                        font-weight: 600;
                                        letter-spacing: 0.02em;
                                    "
                                >
                                    New {{ $formName }} submission
                                </h1>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 24px 32px">
                                <p
                                    style="
                                        margin: 0 0 16px;
                                        font-size: 14px;
                                        line-height: 1.6;
                                    "
                                >
                                    You received a new submission from the
                                    <strong>{{ $formName }}</strong>
                                    form{!! $submittedFrom ? ' on <strong>' . e($submittedFrom) . '</strong>' : '' !!}.
                                </p>
                                <table
                                    role="presentation"
                                    width="100%"
                                    cellpadding="0"
                                    cellspacing="0"
                                    style="border-collapse: collapse"
                                >
                                    @foreach ($fields as $label => $value)
                                        <tr>
                                            <th
                                                align="left"
                                                style="
                                                    padding: 8px 12px;
                                                    background: #f9fafb;
                                                    border: 1px solid #e5e7eb;
                                                    font-size: 12px;
                                                    text-transform: uppercase;
                                                    letter-spacing: 0.05em;
                                                    color: #6b7280;
                                                    width: 35%;
                                                "
                                            >
                                                {{ $label }}
                                            </th>
                                            <td
                                                style="
                                                    padding: 8px 12px;
                                                    border: 1px solid #e5e7eb;
                                                    font-size: 14px;
                                                "
                                            >
                                                {{ $value !== null && $value !== '' ? $value : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="
                                    padding: 16px 32px;
                                    background: #f9fafb;
                                    color: #6b7280;
                                    font-size: 12px;
                                "
                            >
                                Sent automatically by the Capell form handler.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
