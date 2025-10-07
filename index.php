<?php
require_once 'github_info.php';
$github = get_github_info('simbachu', 'personal_webpage');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jennifer Gott</title>

    <!-- Meta Description -->
    <meta name="description" content="Software designer, information engineer, and illustrator based in Gothenburg, Sweden. Currently studying System Development at Chas Academy, specialized in C/C++, embedded development, and technical illustration.">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Jennifer Gott - Software Designer & Information Engineer">
    <meta property="og:description" content="Software designer, information engineer, and illustrator based in Gothenburg, Sweden. Currently studying System Development at Chas Academy, specialized in C/C++, embedded development, and technical illustration.">
    <meta property="og:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/images/og-image.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Jennifer Gott - Software Designer, Information Engineer, and Illustrator">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:site_name" content="Jennifer Gott">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Jennifer Gott - Software Designer & Information Engineer">
    <meta name="twitter:description" content="Software designer, information engineer, and illustrator based in Gothenburg, Sweden. Currently studying System Development at Chas Academy, specialized in C/C++, embedded development, and technical illustration.">
    <meta name="twitter:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/images/og-image.png">
    <meta name="twitter:image:alt" content="Jennifer Gott - Software Designer, Information Engineer, and Illustrator">

    <!-- Additional Meta Tags -->
    <meta name="author" content="Jennifer Gott">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">

    <link rel="stylesheet" href="fonts/inter.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <header>
            <h1>Jennifer Gott</h1>
        <nav>
            <ul>
                <li><a href="#about">About Me</a></li>
                <li><a href="#projects">Projects</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article>
        <section id="about">
            <h2>About Me</h2>
            <figure class="profile-image">
                <img src="images/jg_devops_halftone.png" alt="Jennifer Gott portrait">
            </figure>
            <p>Software designer. Information engineer. Illustrator.</p>
            <p>Based in Gothenburg, Sweden.</p>
            <p>Everything I do is informed by the principles of information design: Is it readable? Is it legible? Is it worth reading? From code to illustration, from wiring diagram layouts to UI design.</p>
            <p>As Product Owner at Volvo Buses, I transformed how service technicians diagnose electrical systems across electromobility platforms, cybersecurity implementations, and active safety systems. I industrialized an extended wiring diagram concept. This reduced fault tracing from an hour to just minutes. The reference materials are now used across 20+ vehicle variants by both aftermarket and R&D teams.</p>
            <p>As Scrum Product Owner for three teams delivering diagnostic content and technical documentation, I worked across the entire product lifecycle. This included R&D roadmap ownership, production process industrialization, and closing the feedback loop from field diagnostics back to development. I drove agile transformation as coach for our international aftermarket department, championing standardized processes to move teams from uncertainty to clear, actionable planning.</p>
            <p>Working with vehicle electronics and communication protocols, I witnessed the automotive industry transforming into a software-defined ecosystem. This inspired me to move from documenting these systems to building them. I enrolled at <a href="https://chasacademy.se/">Chas Academy</a> to study System Development. Now I work with the whole development chain from embedded device development to backend APIs and web applications.</p>
            <p>My experience with vehicle electronics and diagnostics informs my approach to system design. I build solutions that are not just functional, but testable, maintainable, and diagnosable from day one.</p>
            <p>Graduating in 2026, I bring technical depth, full lifecycle thinking, and clear communication to everything I do.</p>

            <h3>Skills</h3>
            <ul>
                <li><strong>C/C++</strong>, embedded and native development</li>
                <li>Test design and program correctness</li>
                <li>Team leader, agile product owner</li>
                <li>Technical illustration</li>
                <li>Layout and typesetting</li>
            </ul>
        </section>

        <section id="projects">
            <h2>Projects</h2>
            <div class="project-grid">
                <div class="project-card">
                    <h3>Aftermarket Wiring Diagram Handbook</h3>
                    <p class="years">2017-present</p>
                    <ul class="tags">
                        <li>Illustration</li>
                        <li>CAD</li>
                        <li>InDesign</li>
                    </ul>
                    <p>Fully illustrated automotive wiring diagram handbooks for service technicians. Over 20 vehicle variants across internal combustion, hybrid-electric, and electric vehicles.</p>
                    <p>Process design from initial proof of concept to final production, with training of staff on different sites. Roadmap ownership and management.</p>
                </div>
                <div class="project-card">
                    <h3>Sunrise alarm clock</h3>
                    <p class="years">2025</p>
                    <ul class="tags">
                        <li>Chas Academy</li>
                        <li>Arduino</li>
                        <li>C++</li>
                        <li>electronics</li>
                    </ul>
                    <p>Breadboard alarm clock with sunrise simulation using daylight LED. Set alarm time and sunrise duration.</p>
                </div>
                <div class="project-card">
                    <h3>Conference room occupancy tracker</h3>
                    <p class="years">2025</p>
                    <ul class="tags">
                        <li>Chas Academy</li>
                        <li>Arduino</li>
                        <li>C++</li>
                        <li>REST API</li>
                        <li>electronics</li>
                    </ul>
                    <p>Room booking system with real-time occupancy information (clearing bookings on vacant rooms). Infrared and temperature sensors for occupancy detection.</p>
                    <p>Part of the Chas Challenge contest. Nominated for Best Embedded Project.</p>
                    <p><a href="https://github.com/Kusten-ar-klar-Chas-Challenge-2025/">GitHub</a></p>
                </div>
                <div class="project-card">
                    <h3>Package tracking system</h3>
                    <p class="years">2025</p>
                    <ul class="tags">
                        <li>Chas Academy</li>
                        <li>Arduino</li>
                        <li>C++</li>
                        <li>BLE</li>
                        <li>REST API</li>
                        <li>electronics</li>
                    </ul>
                    <p>Package tracking system with sensor packages communicating with vehicle based broker, relaying package information to backend Azure service for display in-app or on website. Temperature sensor and alarm for package temperature tracking.</p>
                    <p>Wrote hardware abstraction library to facilitate development on both espidf and Arduino platforms.</p>
                    <p><a href="https://github.com/G1-H25">GitHub</a></p>
                </div>
            </div>
        </section>
        </article>

        <aside id="contact">
            <h2>Contact</h2>
            <ul>
                <li><a href="https://github.com/simbachu">github.com/simbachu</a></li>
                <li><a href="mailto:simbachu@gmail.com">simbachu@gmail.com</a></li>
                <li><a href="https://www.linkedin.com/in/jennifer-jonathan-gott-2233aa294/">linkedin.com/in/jennifer-jonathan-gott</a></li>
            </ul>
        </aside>
    </main>

    <footer>
        <p>This website was automatically uploaded from <a href="https://github.com/simbachu/personal_webpage">GitHub</a> using a <abbr title="Continuous Deployment">CD</abbr> pipeline.</p>

        <div class="github-info">
            <?php if (isset($github['main']) && $github['main']): ?>
            <div class="branch-status">
                <span class="branch-name">
                    <a href="<?php echo htmlspecialchars($github['main']['url']); ?>"
                       title="<?php echo htmlspecialchars($github['main']['message']); ?>">Production</a>
                </span>
                <span class="branch-meta"></span>
                <span class="branch-time">Updated <?php echo format_github_date($github['main']['date']); ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($github['dev']) && $github['dev']): ?>
            <div class="branch-status">
                <span class="branch-name">
                    <a href="<?php echo htmlspecialchars($github['dev']['url']); ?>"
                       title="<?php echo htmlspecialchars($github['dev']['message']); ?>">Dev Preview</a>
                </span>
                <span class="branch-meta">
                    <?php if (isset($github['commits_ahead']) && $github['commits_ahead'] > 0): ?>
                    <?php echo $github['commits_ahead']; ?> commit<?php echo $github['commits_ahead'] !== 1 ? 's' : ''; ?> ahead
                    <?php endif; ?>
                </span>
                <span class="branch-time">Active <?php echo format_github_date($github['dev']['date']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <p>&copy; <?php echo date('Y'); ?> Jennifer Gott. All rights reserved.</p>
    </footer>
</body>
</html>
