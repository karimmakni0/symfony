<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CommentFormType extends AbstractType
{
    // List of bad words to filter - same as in PostFormType for consistency
    private const BAD_WORDS = ['fuck', 'shit', 'ass', 'bitch', 'damn', 'bastard', 'cunt', 'dick', 'asshole'];
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comment', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'form-control py-20 px-30 rounded-4',
                    'rows' => '5',
                    'placeholder' => 'Your comment here...'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a comment',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 1000,
                        'minMessage' => 'Comment should be at least {{ limit }} characters',
                        'maxMessage' => 'Comment cannot be longer than {{ limit }} characters',
                    ]),
                    new Callback([$this, 'validateNoBadWords']),
                ],
            ])
        ;
    }

    public function validateNoBadWords($value, ExecutionContextInterface $context): void
    {
        $text = strtolower($value);
        
        foreach (self::BAD_WORDS as $badWord) {
            if (strpos($text, $badWord) !== false) {
                $context->buildViolation('Your comment contains inappropriate language. Please revise it.')
                    ->addViolation();
                break;
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
