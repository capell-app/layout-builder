<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta
            name="viewport"
            content="width=device-width,initial-scale=1"
        />
        <title>Welcome to {{ $siteName }}</title>
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
                                    padding: 32px;
                                    background: #1a2d6d;
                                    color: #ffffff;
                                    text-align: center;
                                "
                            >
                                <h1
                                    style="
                                        margin: 0;
                                        font-size: 24px;
                                        font-weight: 700;
                                    "
                                >
                                    Welcome to {{ $siteName }}
                                </h1>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="
                                    padding: 32px;
                                    font-size: 15px;
                                    line-height: 1.6;
                                "
                            >
                                <p style="margin: 0 0 16px">
                                    {{ $subscriberName ? 'Hi ' . $subscriberName . ',' : 'Hello,' }}
                                </p>
                                <p style="margin: 0 0 16px">
                                    Thanks for subscribing to the
                                    {{ $siteName }} newsletter. You'll be the
                                    first to hear about new posts, product
                                    updates, and the occasional
                                    behind-the-scenes story.
                                </p>
                                <p style="margin: 0">
                                    We respect your inbox — expect roughly one
                                    email per month.
                                </p>
                            </td>
                        </tr>
                        @if ($unsubscribeUrl)
                            <tr>
                                <td
                                    style="
                                        padding: 16px 32px;
                                        background: #f9fafb;
                                        color: #6b7280;
                                        font-size: 12px;
                                        text-align: center;
                                    "
                                >
                                    Changed your mind?
                                    <a
                                        href="{{ $unsubscribeUrl }}"
                                        style="
                                            color: #6b7280;
                                            text-decoration: underline;
                                        "
                                    >
                                        Unsubscribe here
                                    </a>
                                    .
                                </td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
