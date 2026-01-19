<?php
require_once '../lib/constant.php';

echo "Setting up TimeTrack Pro Extended Features...\n\n";

// Read and execute the schema extensions
$schema_file = __DIR__ . '/../schema_extensions.sql';
if (!file_exists($schema_file)) {
    die("Schema file not found: $schema_file\n");
}

$sql = file_get_contents($schema_file);
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    try {
        if ($db->query($statement)) {
            $success_count++;
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        } else {
            $error_count++;
            echo "✗ Error: " . $db->error . "\n";
            echo "  Statement: " . substr($statement, 0, 100) . "...\n";
        }
    } catch (Exception $e) {
        $error_count++;
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "  Statement: " . substr($statement, 0, 100) . "...\n";
    }
}

echo "\n=== Setup Complete ===\n";
echo "Successful operations: $success_count\n";
echo "Errors: $error_count\n";

if ($error_count === 0) {
    echo "\n🎉 All database extensions have been successfully installed!\n";
    echo "\nNew Features Available:\n";
    echo "- Project Management with Phases and Tasks\n";
    echo "- Team Member Assignment and Role Management\n";
    echo "- Daily Schedule Planning\n";
    echo "- Task Progress Reporting\n";
    echo "- Teacher Timetable Management\n";
    echo "- Activity Fulfillment Tracking\n";
    echo "\nYou can now access these features through the admin dashboard.\n";
} else {
    echo "\n⚠️  Some errors occurred during setup. Please check the error messages above.\n";
}

// Create sample data for demonstration
if ($error_count === 0) {
    echo "\nCreating sample data...\n";
    
    // Update existing admin user with extended permissions
    $admin_update = "UPDATE users SET role = 'admin', can_create_projects = 1, can_manage_team = 1, can_view_reports = 1 WHERE id = 1";
    if ($db->query($admin_update)) {
        echo "✓ Updated admin user permissions\n";
    }
    
    // Create sample project
    $sample_project = "INSERT INTO projects (business_id, name, description, client_name, status, start_date, end_date, budget_hours, created_by) VALUES (1, 'Website Redesign', 'Complete overhaul of the client marketing website including CMS migration to a headless architecture.', 'Acme Corp', 'active', '2023-10-01', '2023-12-31', 120.0, 1)";
    if ($db->query($sample_project)) {
        $project_id = $db->insert_id;
        echo "✓ Created sample project (ID: $project_id)\n";
        
        // Add project owner
        $owner_sql = "INSERT INTO project_members (project_id, user_id, role, added_by) VALUES ($project_id, 1, 'owner', 1)";
        $db->query($owner_sql);
        
        // Create sample phases
        $phases = [
            ['Discovery & Research', 'Initial research and requirements gathering', '2023-10-01', '2023-10-15', 20.0],
            ['UI/UX Design', 'Design mockups and user experience flow', '2023-10-16', '2023-11-15', 40.0],
            ['Development', 'Frontend and backend implementation', '2023-11-16', '2023-12-15', 50.0],
            ['Testing & Launch', 'Quality assurance and deployment', '2023-12-16', '2023-12-31', 10.0]
        ];
        
        foreach ($phases as $i => $phase) {
            $phase_sql = "INSERT INTO project_phases (project_id, name, description, start_date, end_date, estimated_hours, order_index) VALUES ($project_id, '{$phase[0]}', '{$phase[1]}', '{$phase[2]}', '{$phase[3]}', {$phase[4]}, $i)";
            if ($db->query($phase_sql)) {
                $phase_id = $db->insert_id;
                echo "✓ Created phase: {$phase[0]}\n";
                
                // Create sample tasks for each phase
                if ($i === 0) { // Discovery phase
                    $tasks = [
                        ['Client Requirements Meeting', 'Meet with client to gather detailed requirements', 'completed', 'high', '2023-10-05', 4.0],
                        ['Competitor Analysis', 'Research competitor websites and features', 'completed', 'medium', '2023-10-10', 8.0],
                        ['Technical Architecture Planning', 'Plan the technical approach and stack', 'in_progress', 'high', '2023-10-15', 8.0]
                    ];
                } elseif ($i === 1) { // Design phase
                    $tasks = [
                        ['Wireframe Creation', 'Create low-fidelity wireframes', 'in_progress', 'high', '2023-10-25', 12.0],
                        ['UI Design System', 'Develop consistent design system', 'pending', 'medium', '2023-11-01', 16.0],
                        ['Prototype Development', 'Create interactive prototype', 'pending', 'medium', '2023-11-10', 12.0]
                    ];
                } else {
                    $tasks = [];
                }
                
                foreach ($tasks as $task) {
                    $task_sql = "INSERT INTO tasks (project_id, phase_id, name, description, status, priority, due_date, estimated_hours, assigned_to, created_by) VALUES ($project_id, $phase_id, '{$task[0]}', '{$task[1]}', '{$task[2]}', '{$task[3]}', '{$task[4]}', {$task[5]}, 1, 1)";
                    if ($db->query($task_sql)) {
                        echo "  ✓ Created task: {$task[0]}\n";
                    }
                }
            }
        }
    }
    
    echo "\n✅ Sample data created successfully!\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "TimeTrack Pro Extended Features Setup Complete!\n";
echo str_repeat("=", 50) . "\n";
?>