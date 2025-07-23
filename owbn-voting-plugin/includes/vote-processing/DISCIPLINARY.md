# Disciplinary/Punishment Voting System

## Overview

The disciplinary voting system is a unique cascade voting method designed for organizational discipline decisions. It ensures that more severe punishments require stronger consensus.

## How It Works

### Punishment Order (Least to Most Severe)
1. **Condemnation** - Public statement of disapproval (51% required)
2. **Censure** - Formal reprimand (51% required)
3. **Probation** - Monitoring period (51% required)
4. **1 Strike** - First formal warning (51% required)
5. **2 Strikes** - Second formal warning (51% required)
6. **Temporary Ban** - Time-limited exclusion (51% required)
7. **Indefinite Ban/3 Strikes** - Ongoing exclusion (51% required)
8. **Permanent Ban** - Irreversible exclusion (**66.7% supermajority required**)

### The Cascade Algorithm

1. **Initial Count**: Count first-choice votes for each punishment level
2. **Threshold Check**: Starting from the least severe punishment:
   - Check if it meets the required threshold (51% or 66.7%)
   - If YES → That punishment wins
   - If NO → Transfer ALL votes to the next more severe punishment
3. **Continue Cascade**: Repeat until a punishment meets its threshold
4. **No Winner**: If even Permanent Ban doesn't meet 66.7%, no action is taken

### Example Scenario

```
Initial Votes:
- Censure: 30 votes (30%)
- 1 Strike: 25 votes (25%)
- Temporary Ban: 45 votes (45%)
Total: 100 votes

Round 1: Censure has 30% (needs 51%) → Transfer to Probation
Round 2: Probation has 30% (needs 51%) → Transfer to 1 Strike  
Round 3: 1 Strike has 55% (30+25, meets 51%) → WINNER
```

## Implementation Details

### File: `process-disciplinary.php`

Key functions:
- `wpvp_process_disciplinary()` - Main processing function
- `wpvp_get_disciplinary_summary()` - HTML output formatter
- `wpvp_validate_disciplinary_options()` - Validates punishment options

### Database Storage

Results stored in `wpvp_results` table with:
- Round-by-round cascade data
- Transfer logs
- Final punishment decision
- Voter tracking by punishment level

### Special Features

1. **Supermajority for Permanent Ban**: Requires 66.7% to prevent hasty permanent exclusions
2. **Automatic Cascade**: Votes automatically flow to more severe options
3. **Full Audit Trail**: Every transfer is logged
4. **Voter Privacy**: Names tracked but can be hidden in display

## Usage in Code

```php
// Process disciplinary vote
$results = wpvp_process_votes([
    'voting_choice' => 'disciplinary'
], $ballots, $options);

// Check result
if ($results['winner']) {
    echo "Decision: " . $results['winner'];
    echo "Support: " . $results['winner_percentage'] . "%";
} else {
    echo "No punishment reached required threshold";
}
```

## Display Options

The system provides:
- Visual cascade flow diagram
- Round-by-round transfer logs
- Final vote distribution table
- Threshold indicators (✓/✗)
- Optional voter name display

## Key Differences from Other Methods

| Feature | IRV | Disciplinary |
|---------|-----|--------------|
| Direction | Eliminate lowest | Transfer upward |
| Threshold | 50% majority | 51% or 66.7% |
| Options | Any candidates | Fixed punishments |
| Transfer | To next preference | To next severity |
| Purpose | Find consensus winner | Ensure appropriate punishment |

## Validation Rules

1. All 8 punishment levels must be present
2. Order cannot be changed
3. No custom punishment names allowed
4. Permanent Ban always requires supermajority

## Security Considerations

- Voter anonymity options
- Audit trail for accountability  
- No vote changing after submission
- Results locked after calculation

This system ensures that organizational discipline is applied fairly with appropriate community consensus for severity.