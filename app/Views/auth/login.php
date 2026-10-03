<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAVION Pharma - Login</title>
    <link rel="icon" type="image/png" href="<?= base_url('uploads/logo/company_logo.png') ?>">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1A365D 0%, #0B2545 100%);
            display: grid;
            place-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .shell {
            width: min(900px, 100%);
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            background: #ffffff;
        }

        .brand {
            background: #ffffff;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            border-right: 1px solid #f1f5f9;
        }

        .brand img {
            max-width: 220px;
            height: auto;
            margin-bottom: 24px;
            object-fit: contain;
        }

        .brand h1 {
            margin: 0;
            font-size: 24px;
            color: #0B2545;
            font-weight: 800;
            line-height: 1.25;
        }

        .brand p {
            margin: 12px 0 0;
            font-size: 13px;
            color: #64748b;
            line-height: 1.6;
            max-width: 280px;
        }

        .card {
            background: #ffffff;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: #0B2545;
        }

        p.sub {
            margin: 4px 0 24px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        label {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            display: block;
            margin-top: 4px;
            letter-spacing: 0.05em;
        }

        input {
            width: 100%;
            margin: 8px 0 16px;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            box-sizing: border-box;
            background: #f8fafc;
            font-size: 14px;
            color: #0f172a;
            transition: all 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: #3b82f6;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        button {
            width: 100%;
            background: #0B2545;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.02em;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(11, 37, 69, 0.15);
            margin-top: 8px;
        }

        button:hover {
            background: #1A365D;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(11, 37, 69, 0.25);
        }

        button:active {
            transform: translateY(1px);
        }

        .hint {
            color: #64748b;
            font-size: 11px;
            margin-top: 24px;
            line-height: 1.6;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
        }

        .flash {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 600;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        @media (max-width: 860px) {
            .shell {
                grid-template-columns: 1fr;
            }

            .brand {
                padding: 36px 24px;
                border-right: none;
                border-bottom: 1px solid #f1f5f9;
            }

            .card {
                padding: 36px 24px;
            }
        }
    </style>
</head>
<body>
<div class="shell">
    <div class="brand">
        <img src="<?= base_url('uploads/logo/company_logo.png') ?>" alt="RavionPharma Logo">
        <h1>RavionPharma<br>Core ERP</h1>
        <p>Role-based pharma operations platform for planning, E-Detailing, sales, and workflow approvals.</p>
    </div>
    <div class="card">
        <h2>Sign In</h2>
        <p class="sub">Founder / Super Admin Console</p>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
            <div class="flash success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <form method="post" action="/phadmin/login">
            <label>Corporate User ID</label>
            <input name="email" required placeholder="username or email">

            <label>Password</label>
            <input type="password" name="password" required placeholder="********">

            <button type="submit">Authenticate</button>
        </form>
        
    </div>
</div>
</body>
</html>
