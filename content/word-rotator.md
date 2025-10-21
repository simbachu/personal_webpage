# Word Rotator
By: Jennifer Gott
On: 25w39.2

## Presenting a valley
In internet parlance, a _shape rotator_ is someone who easily grasps technical concepts, while a _wordcel_ is a supposed opposite, who has an easier time relating to linguistic concepts.
This is easy to dismiss as ingroup language. The word 'wordcel' has a clear negative meaning. It invokes the violent manosphere involuntary celibacy movement with the -cel ending.
And there's no inherent conflict between mathematics and linguistics for instance, and being good at one does not preclude another.
One example tries to define this line. ^[urban dictionary: wordcel](https://www.urbandictionary.com/define.php?term=wordcel)^ It compares people in deep learning (_rotators_) to people in cryptocurrency (_wordcels_). The deep learning rotators work with tangible results while the wordcels get hyped on words and then rugpulled by the latest crypto scam. The pejorative use is evident.
However, beyond the in- or outgroup use, it is not at all established that these are even different people, or that these groups are in any way in conflict regarding their use of hype terminology.

## Imagine an apple
A popular social media meme, roughly coincidental with the appearance of shape rotator/wordcel is rating your ability to visualize an apple in your head. Can you clearly see it just by thinking about it? Is it represented spatially? Just an outline? A vague color blob or a smell? Or nothing at all?
This relates to aphantasia (inability to visualize mental images). But you don't need a full pathological case to see the connection. Some people can visualize and rotate shapes in their head (shape rotators). Others make connections with words instead.
Looking at famous aphantasics shows this isn't true. ^[wikipedia: aphantasia](https://en.wikipedia.org/wiki/Aphantasia)^ Not being able to see the apple doesn't mean you can't be a doer. John Green has an excellent way of words with very concrete and vivid imagery and is both an author and video essayist; Ed Catmull (of the Catmull-Clark subdivision modifier setting in Blender fame[^pixar]) worked in the overlap between visual arts and computer science; Blake Ross co-created Firefox from a vision of an easier-to-use web browser.

## A specter is haunting the valley
Of course, we cannot discuss tech bro terminology without addressing the elephant in the room^[wikipedia: sense of direction](https://en.wikipedia.org/wiki/Sense_of_direction#Gender_differences_in_self-evaluation_of_sense_of_direction)^. There's a popular stereotype that women are less spatially aware than men. This gender divide fits the terms. STEM subjects are seen as male and shape rotator territory. Humanities and "softer" areas like language are seen as female and wordcel territory.
Of course, connecting a dichotomy of any kind to sexism does not require a lot of gymnastics, given the semiotics involved[^choice] and the hegemony of the gender binary in any given discourse.

## Who would claim wordcel?
Some people enjoy identifying with a terminology even if its origins and common use is a pejorative (like gay), and although I am certain this occurs with wordcels as well, I don't think it's very common. For one, the vague definition and use makes it very easy to claim that you are the shape rotator to someone else's wordcel. We can shift the scope beyond web3 tech. In a broader STEM vs. humanities conflict, the cryptocurrency person becomes the shape rotator. The English major becomes the wordcel. *It is too easy to deflect to reclaim*.
Another aspect is that the true presented dichotomy is between a person with agency and one who only has words and theory left to retreat to. The doer versus the thinker.

## Something to rotate
A large part of the allure of programming is the ability to make something happen. That's the endorphin rush that makes it so engrossing and addictive. But there's also a theoretical and linguistic part that makes up a big part of it. It's hardly controversial to say that people who program computers have preferences towards some programming languages or paradigms, and that a big part of group identification among them is having shared linguistic preferences.
I played many first-person shooters as a teenager. I can still imagine Unreal Tournament maps clearly. I see corridors, hazard spots, and pickup locations. For example, the flak cannon in DM-Deck16 sits above a slime pool with a ramp. It's in a corridor off the main slime pool room.
And for me, a big hurdle I had to get over with getting started with programming was having something to rotate in my head, a shape to work towards. Once I got over that and could start imagining the shape I was building towards, things got a lot easier. But that shape has words.
Introductory programming, after having you do some hello world exercise, often starts with explaining object orientation in the worst possible way, how Cat extends Animal and you give the Cat a meow() method and the rat a squeak() method.
In my world, steeped in Unreal Tournament modding, a cat and a mouse would both be some Actor (since they move about the world) with maybe different hitpoint values and bark strings[^bark]. They're more of a key value pair than a class definition.
This isn't just wordplay.
The fledgling programmer most often has some existing system they want to interface with, and that means you start having to think about the existing linguistics in that system, more than real world analogies.

## Word rotator
I had a revelatory moment of how to put the word and shape together with the model-view-presenter pattern, where you define data types that you will operate on, an end-point view, and transformation of input to a given output in the presenter. A lot of programming challenges become more tangible when I can think about the data model and what the recipient expects in return to their query[^interface].
A lot of important topics in programming start to take its shape from here, such as separation of concerns, generic programming and testing. If you love OOP, you can even use that as an example of a class hierarcy and relationship.
Back around to rotating words, I think the clear use of the words model and view put the word presenter in contrast. The presenter is active, acting on a view based on a model. The causality of the relationship is clear[^mvp].
Shapes and words are not separate. The data model has a shape. The connection to outside systems is also part of the shape. We need to be able to reason about our constructs. The words are the boundaries of our shapes. When you rotate a concept like 'presenter' you are understanding its relationship to adjacent concepts. You also see how it fits into a causality chain. When you extend Animal with Cat, you are struggling to figure out what your domain looks like.

[^pixar]: Also known for other work.
[^choice]: Almost any binary choice can be reduced to gender signifier. "Cats are for girls, dogs are for boys".
[^bark]: You'd think a dog would bark, but it's actually a game development term for phrases a NPC say when you approach.
[^interface]: You can see the extendability of this way of thinking into writing interfaces, which define expectations based on a data model based on a domain.
[^mvp]: I think the major issues are the namespace collission with minimum viable product, especially since they collide in early phases of work. This indicates to me that we should use a more concrete word for the explanation of our initial exploratory work.