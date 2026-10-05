<?php

namespace Database\Seeders;

use App\Enums\BoardListColor;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Seed demo users, their workspaces, and starter boards.
     */
    public function run(): void
    {
        $mandy = User::factory()->withoutTwoFactor()->create([
            'name'  => 'Mandy The Creator',
            'email' => 'mandy.afor@example.com',
        ]);

        $angel = User::factory()->withoutTwoFactor()->create([
            'name'  => 'Angel The Girlfriend',
            'email' => 'angel.afor@example.com',
        ]);

        $mandyWorkspace = Workspace::factory()->forUser($mandy)->create([
            'description' => 'Where I plan what to build next for Afor and keep track of bugs to fix. Ideas start in To Do and move across as I work on them. Angel helps me test new features before they ship.',
        ]);
        $angelWorkspace = Workspace::factory()->forUser($angel)->create([
            'description' => 'My space for trip plans, weekend errands and date ideas. I add things here as soon as they come up so nothing gets forgotten. Mandy can see this workspace too, so we plan together.',
        ]);

        $mandyWorkspace->users()->attach($angel);
        $angelWorkspace->users()->attach($mandy);

        $petWorkspaces = collect([
            'clifford' => ['Clifford The Dog', 'Everything I need to plan my day. Walks come first, then naps, then more walks. Mandy and Angel check in here to see when I am due outside.'],
            'tyler'    => ['Tyler The Cat', 'My list of sunny windowsills and the best boxes in the house. I decide what gets done and when. Mandy and Angel are allowed to look.'],
            'scout'    => ['Scout The Dog', 'Where I keep track of every ball I have lost under the couch. Cooper helps me sniff out new ones. Mandy and Angel help when they roll too far.'],
            'cooper'   => ['Cooper The Dog', 'My plans for park trips, treats and squirrel watch. Scout joins me on most adventures. Mandy and Angel make sure we get home on time.'],
        ])->map(function (array $pet, string $key) use ($mandy, $angel): Workspace {
            [$name, $description] = $pet;

            $owner = User::factory()->withoutTwoFactor()->create([
                'name'  => $name,
                'email' => "{$key}.afor@example.com",
            ]);

            $workspace = Workspace::factory()->forUser($owner)->create(['description' => $description]);
            $workspace->users()->attach([$mandy->id, $angel->id]);

            return $workspace;
        });

        $petWorkspaces['scout']->users()->attach($petWorkspaces['cooper']->owner_id);
        $petWorkspaces['cooper']->users()->attach($petWorkspaces['scout']->owner_id);

        // Clifford is on some of Mandy's boards and Tyler is on none, so Tyler sees the empty state. Scout never joined.
        $mandyWorkspace->users()->attach([$petWorkspaces['clifford']->owner_id, $petWorkspaces['tyler']->owner_id]);

        $workspaces = ['mandy' => $mandyWorkspace, 'angel' => $angelWorkspace, ...$petWorkspaces->all()];
        $users = [
            'mandy' => $mandy,
            'angel' => $angel,
            ...$petWorkspaces->map(fn (Workspace $workspace): User => $workspace->owner)->all(),
        ];

        foreach ($this->boards() as $ownerKey => $boards) {
            $workspace = $workspaces[$ownerKey];

            foreach ($boards as $boardData) {
                $board = Board::factory()
                    ->for($workspace)
                    ->withMembers(collect($boardData['members'] ?? [])->map(fn (string $userKey): User => $users[$userKey])->all())
                    ->create(['name' => $boardData['name']]);

                if ($boardData['archived'] ?? false) {
                    $board->update(['archived_at' => now(), 'archived_by' => $workspace->owner_id]);
                }

                foreach ($boardData['starredBy'] ?? [] as $userKey) {
                    $users[$userKey]->favoriteBoards()->attach($board);
                }

                foreach (array_values($boardData['lists']) as $listOrder => $listData) {
                    $boardList = BoardList::factory()->for($board)->create([
                        'name'  => $listData['name'],
                        'order' => $listOrder,
                        'color' => $listData['color']->value,
                    ]);

                    Card::factory()->for($boardList)->createMany(
                        collect($listData['cards'])->values()->map(fn (string|array $card, int $cardOrder): array => [
                            'name'        => is_array($card) ? $card[0] : $card,
                            'description' => is_array($card) ? "<p>{$card[1]}</p>" : null,
                            'order'       => $cardOrder,
                        ])->all(),
                    );
                }
            }
        }
    }

    /**
     * Demo boards keyed by the owner of the workspace they belong to. The owner sees every board in their
     * workspace; anyone else only sees the boards that list them as members. A card is either a name or a
     * [name, description] pair.
     *
     * @return array<string, list<array{name: string, archived?: bool, members?: list<string>, starredBy?: list<string>, lists: list<array{name: string, color: BoardListColor, cards: list<string|array{string, string}>}>}>>
     */
    private function boards(): array
    {
        return [
            'mandy' => [
                [
                    'name'      => 'Afor Roadmap',
                    'members'   => ['angel', 'clifford'],
                    'starredBy' => ['mandy'],
                    'lists'     => [
                        ['name' => 'Backlog', 'color' => BoardListColor::NEUTRAL, 'cards' => [
                            ['Due dates on cards', 'Let people set a due date and show overdue cards in red on the board.'],
                            'Labels and filters for cards',
                            'Activity log for each board',
                            'Weekly email summary of board changes',
                        ]],
                        ['name' => 'Up Next', 'color' => BoardListColor::BLUE, 'cards' => [
                            'Move cards between boards',
                            ['Keyboard shortcuts', 'N for a new card, E to edit, and arrow keys to move between lists.'],
                        ]],
                        ['name' => 'In Progress', 'color' => BoardListColor::YELLOW, 'cards' => [
                            ['Mobile layout for the board view', 'Lists should scroll sideways with snap points, and cards need bigger tap targets.'],
                            'Empty state for new boards',
                        ]],
                        ['name' => 'Shipped', 'color' => BoardListColor::GREEN, 'cards' => [
                            'Workspace invite links',
                            'Archive and restore boards',
                            'Star favourite boards',
                            'Owner-only workspace settings',
                        ]],
                    ],
                ],
                [
                    'name'    => 'Bug Tracker',
                    'members' => ['angel', 'clifford'],
                    'lists'   => [
                        ['name' => 'Reported', 'color' => BoardListColor::RED, 'cards' => [
                            ['Card description is lost when the dialog closes', 'Happens when closing with Escape before the editor saves. Angel hit this twice.'],
                            'Sidebar flickers when switching workspaces',
                        ]],
                        ['name' => 'Investigating', 'color' => BoardListColor::ORANGE, 'cards' => [
                            ['List order resets after fast drags', 'Only when two lists are dropped within a second of each other. Probably a race between reorder requests.'],
                        ]],
                        ['name' => 'Fixed', 'color' => BoardListColor::GREEN, 'cards' => [
                            'Members could open workspace settings',
                            'Removing a workspace logo did nothing',
                            'Archived boards showed up on the boards page',
                        ]],
                    ],
                ],
                [
                    'name'  => 'Launch Checklist',
                    'lists' => [
                        ['name' => 'Before Launch', 'color' => BoardListColor::PURPLE, 'cards' => [
                            'Write the privacy policy and terms of use',
                            'Set up error monitoring',
                            ['Load test the board page', 'Aim for a board with 20 lists and 500 cards loading in under a second.'],
                        ]],
                        ['name' => 'Launch Day', 'color' => BoardListColor::SUNSET, 'cards' => [
                            'Announce on social media',
                            'Watch the error logs',
                        ]],
                        ['name' => 'After Launch', 'color' => BoardListColor::AURORA, 'cards' => [
                            'Ask the first users for feedback',
                            'Plan the next round of features',
                        ]],
                    ],
                ],
                [
                    'name'     => 'First Prototype',
                    'members'  => ['angel'],
                    'archived' => true,
                    'lists'    => [
                        ['name' => 'Done', 'color' => BoardListColor::GREEN, 'cards' => [
                            'Sketch the board layout on paper',
                            'Pick the tech stack',
                            'Build drag and drop for cards',
                        ]],
                    ],
                ],
            ],
            'angel' => [
                [
                    'name'      => 'Beach Weekend',
                    'members'   => ['mandy'],
                    'starredBy' => ['angel', 'mandy'],
                    'lists'     => [
                        ['name' => 'Ideas', 'color' => BoardListColor::ANGEL, 'cards' => [
                            'Sunrise walk along the shore',
                            ['Snorkelling tour', 'The tour leaves at 9 and lasts three hours. Bring our own masks if we can.'],
                            'Seafood dinner by the water',
                        ]],
                        ['name' => 'Booked', 'color' => BoardListColor::BLUE, 'cards' => [
                            ['Bus tickets for Friday night', 'Leaves at 10 PM from the main terminal. Tickets are saved in my email.'],
                            'Beach cabin for two nights',
                        ]],
                        ['name' => 'Packing List', 'color' => BoardListColor::YELLOW, 'cards' => [
                            'Sunscreen and hats',
                            'Swimsuits and towels',
                            'Power bank',
                            'Snacks for the bus',
                        ]],
                    ],
                ],
                [
                    'name'      => 'Weekend Errands',
                    'members'   => ['mandy'],
                    'starredBy' => ['angel'],
                    'lists'     => [
                        ['name' => 'This Week', 'color' => BoardListColor::ORANGE, 'cards' => [
                            'Groceries for the week',
                            'Pick up the laundry',
                            ['Pay the electricity bill', 'Due on Friday. The account number is on the fridge.'],
                        ]],
                        ['name' => 'Waiting On', 'color' => BoardListColor::PURPLE, 'cards' => [
                            'Delivery of the new desk lamp',
                            'Vet to confirm Tyler\'s checkup time',
                        ]],
                        ['name' => 'Done', 'color' => BoardListColor::GREEN, 'cards' => [
                            'Renew the library card',
                            'Return the borrowed blender',
                        ]],
                    ],
                ],
                [
                    'name'  => 'Date Ideas',
                    'lists' => [
                        ['name' => 'Want to Try', 'color' => BoardListColor::ANGEL, 'cards' => [
                            'Pottery class',
                            'Picnic in the park',
                            'Cook ramen from scratch',
                            'Stargazing outside the city',
                        ]],
                        ['name' => 'Planned', 'color' => BoardListColor::SUNSET, 'cards' => [
                            ['Studio Ghibli movie night', 'Spirited Away, then Howl\'s Moving Castle. Mandy is in charge of popcorn.'],
                        ]],
                        ['name' => 'Done It', 'color' => BoardListColor::GREEN, 'cards' => [
                            'Board game café',
                            'Night market food crawl',
                        ]],
                    ],
                ],
            ],
            'clifford' => [
                [
                    'name'    => 'Daily Routine',
                    'members' => ['mandy', 'angel'],
                    'lists'   => [
                        ['name' => 'Morning', 'color' => BoardListColor::SUNSET, 'cards' => [
                            'Wake Mandy up at 6',
                            'First walk around the block',
                            'Breakfast',
                        ]],
                        ['name' => 'Afternoon', 'color' => BoardListColor::YELLOW, 'cards' => [
                            'Nap on the couch',
                            'Bark at the mail carrier',
                            'Second walk',
                        ]],
                        ['name' => 'Evening', 'color' => BoardListColor::PURPLE, 'cards' => [
                            'Dinner',
                            'Zoomies in the yard',
                            'Sleep at the foot of the bed',
                        ]],
                    ],
                ],
                [
                    'name'    => 'Walk Routes',
                    'members' => ['angel'],
                    'lists'   => [
                        ['name' => 'Favourites', 'color' => BoardListColor::GREEN, 'cards' => [
                            'Park loop past the pond',
                            ['Street with the friendly baker', 'She sometimes has a crust for me. Walk slowly past the door.'],
                        ]],
                        ['name' => 'Want to Explore', 'color' => BoardListColor::BLUE, 'cards' => [
                            'New trail behind the school',
                        ]],
                        ['name' => 'Avoid', 'color' => BoardListColor::RED, 'cards' => [
                            'House with the grumpy cat',
                            'Road works on Main Street',
                        ]],
                    ],
                ],
            ],
            'tyler' => [
                [
                    'name'    => 'Sunny Spots',
                    'members' => ['mandy', 'angel'],
                    'lists'   => [
                        ['name' => 'Morning Sun', 'color' => BoardListColor::SUNSET, 'cards' => [
                            'Kitchen windowsill',
                            'Top of the bookshelf',
                        ]],
                        ['name' => 'Afternoon Sun', 'color' => BoardListColor::YELLOW, 'cards' => [
                            'Living room rug',
                            ['Mandy\'s laptop keyboard', 'Warm all day. Mandy disagrees with this spot.'],
                        ]],
                        ['name' => 'Evening Warmth', 'color' => BoardListColor::PURPLE, 'cards' => [
                            'Warm spot on the bed',
                        ]],
                    ],
                ],
                [
                    'name'  => 'Box Inspections',
                    'lists' => [
                        ['name' => 'To Inspect', 'color' => BoardListColor::ORANGE, 'cards' => [
                            'Delivery box by the front door',
                        ]],
                        ['name' => 'Approved', 'color' => BoardListColor::GREEN, 'cards' => [
                            'Shoe box in the closet',
                            'Laundry basket',
                        ]],
                        ['name' => 'Rejected', 'color' => BoardListColor::RED, 'cards' => [
                            ['Box that is too small', 'Sat in it anyway. Still rejected.'],
                        ]],
                    ],
                ],
                [
                    'name'    => 'Demands',
                    'members' => ['mandy'],
                    'lists'   => [
                        ['name' => 'Pending', 'color' => BoardListColor::ANGEL, 'cards' => [
                            'Dinner 30 minutes early',
                            'Open the bedroom door',
                            'Close the bedroom door',
                        ]],
                        ['name' => 'Granted', 'color' => BoardListColor::GREEN, 'cards' => [
                            'New scratching post',
                        ]],
                    ],
                ],
            ],
            'scout' => [
                [
                    'name'    => 'Lost Ball Tracker',
                    'members' => ['mandy', 'angel', 'cooper'],
                    'lists'   => [
                        ['name' => 'Lost', 'color' => BoardListColor::RED, 'cards' => [
                            'Red ball under the couch',
                            'Tennis ball over the fence',
                            'Squeaky ball somewhere in the garden',
                        ]],
                        ['name' => 'Sniffing It Out', 'color' => BoardListColor::ORANGE, 'cards' => [
                            ['Blue ball behind the fridge', 'Cooper smells it too. We need someone with hands.'],
                        ]],
                        ['name' => 'Found', 'color' => BoardListColor::GREEN, 'cards' => [
                            'Yellow ball in the toy basket',
                            'Green ball under the bed',
                        ]],
                    ],
                ],
                [
                    'name'    => 'Training Goals',
                    'members' => ['angel'],
                    'lists'   => [
                        ['name' => 'Learning', 'color' => BoardListColor::BLUE, 'cards' => [
                            'Drop the ball the first time I am asked',
                            'Wait at the door',
                        ]],
                        ['name' => 'Practising', 'color' => BoardListColor::YELLOW, 'cards' => [
                            'Roll over',
                            ['Stay for ten seconds', 'Got to seven seconds on Tuesday. Treats help.'],
                        ]],
                        ['name' => 'Mastered', 'color' => BoardListColor::GREEN, 'cards' => [
                            'Sit',
                            'Paw',
                        ]],
                    ],
                ],
            ],
            'cooper' => [
                [
                    'name'    => 'Park Adventures',
                    'members' => ['mandy', 'angel', 'scout'],
                    'lists'   => [
                        ['name' => 'Planned', 'color' => BoardListColor::BLUE, 'cards' => [
                            ['Dog park by the river with Scout', 'Saturday morning, before it gets too hot.'],
                            'Beach day',
                        ]],
                        ['name' => 'This Weekend', 'color' => BoardListColor::SUNSET, 'cards' => [
                            'Morning run in the big park',
                        ]],
                        ['name' => 'Done', 'color' => BoardListColor::GREEN, 'cards' => [
                            'Hike up to the lookout',
                            'Swim in the lake',
                        ]],
                    ],
                ],
                [
                    'name'    => 'Squirrel Watch',
                    'members' => ['scout'],
                    'lists'   => [
                        ['name' => 'Spotted', 'color' => BoardListColor::ORANGE, 'cards' => [
                            'Big grey one in the oak tree',
                            'Little one on the fence',
                        ]],
                        ['name' => 'Chased', 'color' => BoardListColor::RED, 'cards' => [
                            'The one stealing bird seed',
                        ]],
                        ['name' => 'Got Away', 'color' => BoardListColor::NEUTRAL, 'cards' => [
                            'All of them so far',
                        ]],
                    ],
                ],
                [
                    'name'    => 'Treat Rankings',
                    'members' => ['angel'],
                    'lists'   => [
                        ['name' => 'Top Tier', 'color' => BoardListColor::GREEN, 'cards' => [
                            'Peanut butter biscuits',
                            'Cheese cubes',
                        ]],
                        ['name' => 'Fine', 'color' => BoardListColor::YELLOW, 'cards' => [
                            'Carrot sticks',
                        ]],
                        ['name' => 'Never Again', 'color' => BoardListColor::RED, 'cards' => [
                            ['Lemon slice', 'Do not ask.'],
                        ]],
                    ],
                ],
            ],
        ];
    }
}
